<?php
declare(strict_types=1);
namespace App;

use PDO;
use PDOException;

final class Database {
    private static ?PDO $pdo = null;

    public static function connection(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;

        if (!extension_loaded('pdo_pgsql')) {
            throw new \RuntimeException('Falta la extensión pdo_pgsql de PHP. Actívala en cPanel o en .');
        }

        $host = Config::get('DB_HOST', '');
        $port = Config::get('DB_PORT', '5432');
        $name = Config::get('DB_DATABASE', '');
        $user = Config::get('DB_USERNAME', '');
        $pass = Config::get('DB_PASSWORD', '');
        $sslmode = Config::get('DB_SSLMODE', 'require');

        if ($host === '' || $name === '' || $user === '' || $pass === '') {
            throw new \RuntimeException('Falta configurar DB_HOST, DB_DATABASE, DB_USERNAME o DB_PASSWORD en backend/.env.');
        }
        if (!in_array($sslmode, ['disable','allow','prefer','require','verify-ca','verify-full'], true)) {
            throw new \RuntimeException('DB_SSLMODE tiene un valor no permitido.');
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$sslmode}";
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 8,
            ]);
            self::$pdo->exec("SET TIME ZONE 'America/Guatemala'");
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo conectar a PostgreSQL. aqui esta el error: ' . $e->getMessage());

            // El mensaje detallado se registra en el log central; nunca incluir credenciales.
            Logger::write('error', 'Falló la conexión con PostgreSQL', [
                'sqlstate' => (string)$e->getCode(),
                'driver' => 'pgsql',
                'host_configurado' => $host,
                'base_configurada' => $name
            ]);
            throw new \RuntimeException('No se pudo conectar a PostgreSQL. Verifica el host, base de datos, usuario, contraseña, SSL y la extensión pdo_pgsql.');
        }
        return self::$pdo;
    }
}
