<?php
declare(strict_types=1);
namespace App;

final class Api {
    public static function dispatch(): never {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = preg_replace('#/+#', '/', $path);
        $path = preg_replace('#^.*?/api(?=/|$)#', '', $path) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '//') $path = '/';

        if ($method === 'GET' && $path === '/health') {
            try {
                Database::connection()->query('SELECT 1')->fetchColumn();
                Http::json(['ok'=>true,'data'=>['status'=>'ok','database'=>'connected','driver'=>'pgsql','time'=>date(DATE_ATOM)]]);
            } catch (\Throwable $e) {
                Logger::write('error','Health check de base de datos falló',['type'=>get_class($e),'code'=>(string)$e->getCode()]);
                Http::json(['ok'=>false,'error'=>['code'=>'DEPENDENCY_UNAVAILABLE','message'=>'La API no puede conectarse a la base de datos.']],503);
            }
        }
        $pdo = Database::connection();
        if ($method === 'POST' && $path === '/auth/login') self::login($pdo);
        if ($method === 'POST' && $path === '/auth/logout') self::logout($pdo);

        $user = Auth::user();
        if ($method === 'GET' && $path === '/auth/me') Http::json(['ok'=>true,'data'=>$user]);

        if ($method === 'GET' && $path === '/dashboard') {
            $stats = [
                'reservas_pendientes'=>(int)$pdo->query("SELECT COUNT(*) FROM reservas WHERE estado='pendiente'")->fetchColumn(),
                'reservas_confirmadas'=>(int)$pdo->query("SELECT COUNT(*) FROM reservas WHERE estado='confirmada' AND fecha_inicio >= CURRENT_DATE")->fetchColumn(),
                'salones_activos'=>(int)$pdo->query("SELECT COUNT(*) FROM salones WHERE activo = TRUE")->fetchColumn(),
                'reservas_mes'=>(int)$pdo->query("SELECT COUNT(*) FROM reservas WHERE fecha_inicio >= date_trunc('month', CURRENT_DATE) AND fecha_inicio < date_trunc('month', CURRENT_DATE) + INTERVAL '1 month' AND estado <> 'cancelada'")->fetchColumn()
            ];
            Http::json(['ok'=>true,'data'=>$stats]);
        }

        if ($method === 'GET' && $path === '/reservas') Http::json(['ok'=>true,'data'=>Reservations::list($pdo, $_GET)]);
        if ($method === 'POST' && $path === '/reservas') {
            Auth::requireRole($user, ['secretaria']);
            $id = Reservations::create($pdo, Http::input(), $user);
            Audit::record((int)$user['id'],'crear','reserva',$id);
            Http::json(['ok'=>true,'data'=>['id'=>$id]], 201);
        }

        if (preg_match('#^/reservas/(\d+)/(confirmar|cancelar|finalizar)$#', $path, $m) && $method === 'PATCH') {
            Auth::requireRole($user, ['secretaria']);
            $id=(int)$m[1]; $action=$m[2];
            $map=['confirmar'=>'confirmada','cancelar'=>'cancelada','finalizar'=>'finalizada'];
            $reason=trim((string)(Http::input()['motivo'] ?? ''));
            $sql='UPDATE reservas SET estado=?, actualizado_en=CURRENT_TIMESTAMP'.($action==='cancelar'?', motivo_cancelacion=?':'').' WHERE id=? AND estado IN ('.($action==='confirmar'?"'pendiente'":($action==='cancelar'?"'pendiente','confirmada'":"'confirmada'")).')';
            $stmt=$pdo->prepare($sql);
            $params=[$map[$action]];
            if ($action==='cancelar') $params[]=$reason ?: null;
            $params[]=$id; $stmt->execute($params);
            if (!$stmt->rowCount()) Http::json(['ok'=>false,'error'=>['code'=>'NOT_FOUND_OR_STATE','message'=>'No existe la reserva o su estado no permite este cambio.']], 409);
            Audit::record((int)$user['id'],$action,'reserva',$id,['motivo'=>$reason]);
            Http::json(['ok'=>true,'data'=>['id'=>$id,'estado'=>$map[$action]]]);
        }

        if ($method === 'GET' && $path === '/salones') {
            $st=$pdo->query('SELECT id,nombre,ubicacion,capacidad,descripcion,activo FROM salones ORDER BY nombre');
            Http::json(['ok'=>true,'data'=>$st->fetchAll()]);
        }
        if ($method === 'POST' && $path === '/salones') {
            Auth::requireRole($user,['secretaria']); $d=Http::input();
            $name=trim((string)($d['nombre']??'')); $cap=filter_var($d['capacidad']??1,FILTER_VALIDATE_INT);
            if ($name==='' || !$cap || $cap<1) throw new \InvalidArgumentException('Nombre y capacidad válida son obligatorios.');
            $st=$pdo->prepare('INSERT INTO salones (nombre,ubicacion,capacidad,descripcion,activo) VALUES (?,?,?,?,TRUE) RETURNING id');
            $st->execute([$name,trim((string)($d['ubicacion']??''))?:null,$cap,trim((string)($d['descripcion']??''))?:null]);
            $id=(int)$st->fetchColumn();
            Audit::record((int)$user['id'],'crear','salon',$id);
            Http::json(['ok'=>true,'data'=>['id'=>$id]],201);
        }

        if ($method === 'GET' && $path === '/equipos') Http::json(['ok'=>true,'data'=>$pdo->query('SELECT id,nombre,cantidad_total,descripcion,activo FROM equipos ORDER BY nombre')->fetchAll()]);
        if ($method === 'POST' && $path === '/equipos') {
            Auth::requireRole($user,['secretaria']); $d=Http::input();
            $name=trim((string)($d['nombre']??'')); $qty=filter_var($d['cantidad_total']??1,FILTER_VALIDATE_INT);
            if ($name==='' || !$qty || $qty<1) throw new \InvalidArgumentException('Nombre y cantidad válida son obligatorios.');
            $st=$pdo->prepare('INSERT INTO equipos (nombre,cantidad_total,descripcion,activo) VALUES (?,?,?,TRUE) RETURNING id');
            $st->execute([$name,$qty,trim((string)($d['descripcion']??''))?:null]);
            $id=(int)$st->fetchColumn();
            Audit::record((int)$user['id'],'crear','equipo',$id);
            Http::json(['ok'=>true,'data'=>['id'=>$id]],201);
        }

        if ($method === 'GET' && $path === '/auditoria') {
            Auth::requireRole($user,['secretaria']);
            $st=$pdo->query('SELECT a.id,a.accion,a.entidad,a.entidad_id,a.detalle,a.ip,a.creado_en,u.nombre AS usuario FROM auditoria a LEFT JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.id DESC LIMIT 200');
            Http::json(['ok'=>true,'data'=>$st->fetchAll()]);
        }

        Http::json(['ok'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Ruta no encontrada.']],404);
    }

    private static function login(\PDO $pdo): never {
        $d=Http::input(); $email=strtolower(trim((string)($d['correo']??''))); $password=(string)($d['password']??'');
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') throw new \InvalidArgumentException('Correo y contraseña son obligatorios.');
        $st=$pdo->prepare('SELECT u.id,u.nombre,u.correo,u.password_hash,u.activo,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.correo=? LIMIT 1');
        $st->execute([$email]); $u=$st->fetch();
        if (!$u || !in_array($u['activo'], [true, 1, '1', 't', 'true'], true) || !password_verify($password,$u['password_hash'])) {
            usleep(250000);
            Http::json(['ok'=>false,'error'=>['code'=>'INVALID_CREDENTIALS','message'=>'Credenciales incorrectas.']],401);
        }
        $secret=Config::get('JWT_SECRET','');
        if (strlen((string)$secret)<32) throw new \RuntimeException('JWT_SECRET no está configurado de forma segura.');
        $ttl=max(5,min(1440,(int)Config::get('JWT_TTL_MINUTES','60')));
        $now=time(); $jti=bin2hex(random_bytes(16));
        $payload=['iss'=>Config::get('JWT_ISSUER','reservas-institucional'),'iat'=>$now,'nbf'=>$now,'exp'=>$now+$ttl*60,'sub'=>(string)$u['id'],'jti'=>$jti];
        $token=Jwt::encode($payload,(string)$secret);
        Audit::record((int)$u['id'],'login','usuario',(int)$u['id']);
        Http::json(['ok'=>true,'data'=>['token'=>$token,'expires_in'=>$ttl*60,'user'=>['id'=>(int)$u['id'],'nombre'=>$u['nombre'],'correo'=>$u['correo'],'rol'=>$u['rol']]]]);
    }

    private static function logout(\PDO $pdo): never {
        $user=Auth::user(); $token=Http::bearer();
        $payload=Jwt::decode($token,(string)Config::get('JWT_SECRET',''));
        $exp=(int)($payload['exp']??time()); $jti=(string)($payload['jti']??'');
        if ($jti!=='') {
            $st=$pdo->prepare('INSERT INTO tokens_revocados (jti,expira_en) VALUES (?,?) ON CONFLICT (jti) DO NOTHING');
            $st->execute([$jti,date('Y-m-d H:i:s',$exp)]);
        }
        Audit::record((int)$user['id'],'logout','usuario',(int)$user['id']);
        Http::json(['ok'=>true,'data'=>['message'=>'Sesión cerrada.']]);
    }
}
