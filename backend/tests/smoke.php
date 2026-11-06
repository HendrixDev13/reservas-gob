<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$checks = [
    dirname(__DIR__) . '/public/index.php',
    dirname(__DIR__) . '/src/Api.php',
    dirname(__DIR__) . '/src/Reservations.php',
    dirname(__DIR__) . '/src/Jwt.php',
    $root . '/database/schema.sql',
    dirname(__DIR__) . '/.env.example',
];
$failed = false;
foreach ($checks as $file) {
    $ok = is_file($file);
    echo ($ok ? '[OK] ' : '[FALLO] ') . $file . PHP_EOL;
    $failed = $failed || !$ok;
}
if (PHP_VERSION_ID < 80200) { echo "[FALLO] Se requiere PHP 8.2+\n"; $failed=true; }
else echo "[OK] PHP " . PHP_VERSION . PHP_EOL;
if (!extension_loaded('pdo_pgsql')) {
    echo "[AVISO] pdo_pgsql no está habilitada en este entorno; la conexión Neon no se puede probar aquí.\n";
} else {
    echo "[OK] Extensión pdo_pgsql disponible\n";
}
exit($failed ? 1 : 0);
