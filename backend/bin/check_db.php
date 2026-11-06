<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Logger.php';
require dirname(__DIR__) . '/src/Database.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit("No disponible por web.\n"); }
\App\Config::load(dirname(__DIR__));

try {
    $pdo = \App\Database::connection();
    $row = $pdo->query('SELECT current_database() AS database_name, current_user AS database_user, version() AS server_version')->fetch();
    echo "[OK] Conexión PostgreSQL establecida.\n";
    echo "Base de datos: " . ($row['database_name'] ?? '(desconocida)') . "\n";
    echo "Usuario: " . ($row['database_user'] ?? '(desconocido)') . "\n";
    echo "Motor: PostgreSQL\n";
    exit(0);
} catch (Throwable $e) {
    \App\Logger::write('error', 'Prueba CLI de conexión a PostgreSQL falló', [
        'type' => get_class($e),
        'code' => (string)$e->getCode()
    ]);
    fwrite(STDERR, "[FALLO] No se pudo conectar a PostgreSQL. Revisa backend/.env, la extensión pdo_pgsql y backend/storage/logs.\n");
    exit(1);
}
