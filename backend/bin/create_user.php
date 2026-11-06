<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Logger.php';
require dirname(__DIR__) . '/src/Database.php';
\App\Config::load(dirname(__DIR__));

if (PHP_SAPI !== 'cli') { http_response_code(404); exit("No disponible por web.\n"); }
$role = $argv[1] ?? ''; $email = strtolower(trim($argv[2] ?? '')); $name = trim($argv[3] ?? '');
if (!in_array($role,['secretaria','consulta'],true) || !filter_var($email,FILTER_VALIDATE_EMAIL) || $name==='') {
    fwrite(STDERR,"Uso: php bin/create_user.php secretaria|consulta correo@dominio.gt \"Nombre\"\n"); exit(2);
}
fwrite(STDOUT,"Contraseña nueva (mínimo 12 caracteres): ");
if (strncasecmp(PHP_OS,'WIN',3)===0) {
    $password=trim((string)fgets(STDIN));
} else {
    system('stty -echo');
    $password=trim((string)fgets(STDIN));
    system('stty echo');
    fwrite(STDOUT,PHP_EOL);
}
if (strlen($password)<12) { fwrite(STDERR,"La contraseña debe tener al menos 12 caracteres.\n"); exit(2); }

try {
    $pdo=\App\Database::connection();
    $q=$pdo->prepare('SELECT id FROM roles WHERE nombre=?');
    $q->execute([$role]);
    $roleId=$q->fetchColumn();
    if ($roleId === false) {
        fwrite(STDERR,"No existe el rol '{$role}'. Ejecuta database/schema.sql en Neon primero.\n"); exit(3);
    }
    $st=$pdo->prepare('INSERT INTO usuarios (nombre,correo,password_hash,rol_id) VALUES (?,?,?,?)');
    $st->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),(int)$roleId]);
    fwrite(STDOUT,"Usuario creado correctamente: {$email} (rol: {$role}).\n");
} catch (\PDOException $e) {
    if ((string)$e->getCode()==='23505') {
        fwrite(STDERR,"No se creó la cuenta: el correo ya existe o hay un valor duplicado.\n");
        exit(4);
    }
    \App\Logger::write('error','No se pudo crear usuario desde CLI',['sqlstate'=>(string)$e->getCode()]);
    fwrite(STDERR,"No se pudo crear el usuario. Revisa la configuración y backend/storage/logs.\n");
    exit(1);
} catch (\Throwable $e) {
    \App\Logger::write('error','Fallo creando usuario desde CLI',['type'=>get_class($e),'message'=>$e->getMessage()]);
    fwrite(STDERR,"No se pudo crear el usuario. Revisa la configuración y backend/storage/logs.\n");
    exit(1);
}
