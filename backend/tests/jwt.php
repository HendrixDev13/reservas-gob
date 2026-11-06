<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/Jwt.php';

$secret = bin2hex(random_bytes(32));
$token = \App\Jwt::encode(['iss'=>'test','iat'=>time(),'nbf'=>time(),'exp'=>time()+60,'sub'=>'1','jti'=>'test-jti'], $secret);
$claims = \App\Jwt::decode($token, $secret);
if (($claims['iss'] ?? null) !== 'test') {
    fwrite(STDERR, "[FALLO] No se recuperaron los claims esperados.\n");
    exit(1);
}
echo "[OK] JWT válido se firma y verifica correctamente.\n";

$parts = explode('.', $token);
$parts[1] = rtrim(strtr(base64_encode('{"iss":"atacante","exp":'.(time()+60).'}'), '+/', '-_'), '=');
$tampered = implode('.', $parts);
try {
    \App\Jwt::decode($tampered, $secret);
    fwrite(STDERR, "[FALLO] El token modificado fue aceptado.\n");
    exit(1);
} catch (Throwable) {
    echo "[OK] JWT alterado se rechaza.\n";
}

try {
    \App\Jwt::decode(\App\Jwt::encode(['exp'=>time()-1], $secret), $secret);
    fwrite(STDERR, "[FALLO] El token expirado fue aceptado.\n");
    exit(1);
} catch (Throwable) {
    echo "[OK] JWT expirado se rechaza.\n";
}
