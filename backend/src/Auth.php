<?php
declare(strict_types=1);
namespace App;

final class Auth {
    public static function user(bool $required = true): ?array {
        $token = Http::bearer();
        if (!$token) {
            if ($required) Http::json(['ok'=>false,'error'=>['code'=>'UNAUTHORIZED','message'=>'Autenticación requerida.']], 401);
            return null;
        }
        try {
            $secret = Config::get('JWT_SECRET', '');
            if (strlen((string)$secret) < 32) throw new \RuntimeException('JWT_SECRET no está configurado de forma segura.');
            $payload = Jwt::decode($token, (string)$secret);
            if (($payload['iss'] ?? '') !== Config::get('JWT_ISSUER', 'reservas-institucional')) throw new \UnexpectedValueException('Issuer inválido.');
            $pdo = Database::connection();
            $q = $pdo->prepare('SELECT jti FROM tokens_revocados WHERE jti=? AND expira_en > NOW()');
            $q->execute([(string)($payload['jti'] ?? '')]);
            if ($q->fetch()) throw new \UnexpectedValueException('Token revocado.');
            $q = $pdo->prepare('SELECT u.id,u.nombre,u.correo,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.activo IS TRUE');
            $q->execute([(int)($payload['sub'] ?? 0)]);
            $user = $q->fetch();
            if (!$user) throw new \UnexpectedValueException('Usuario inactivo o inexistente.');
            return $user;
        } catch (\Throwable $e) {
            Logger::write('warning', 'Falló autenticación', ['reason'=>$e->getMessage()]);
            if ($required) Http::json(['ok'=>false,'error'=>['code'=>'UNAUTHORIZED','message'=>'Token inválido o expirado.']], 401);
            return null;
        }
    }

    public static function requireRole(array $user, array $roles): void {
        if (!in_array($user['rol'], $roles, true)) {
            Http::json(['ok'=>false,'error'=>['code'=>'FORBIDDEN','message'=>'No tienes permiso para realizar esta acción.']], 403);
        }
    }
}
