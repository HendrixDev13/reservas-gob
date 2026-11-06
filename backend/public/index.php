<?php
declare(strict_types=1);

use App\Config;
use App\Logger;
use App\Http;

// Cargar las clases de la aplicación
require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Logger.php';
require dirname(__DIR__) . '/src/Database.php';
require dirname(__DIR__) . '/src/Http.php';
require dirname(__DIR__) . '/src/Jwt.php';
require dirname(__DIR__) . '/src/Auth.php';
require dirname(__DIR__) . '/src/Audit.php';
require dirname(__DIR__) . '/src/Reservations.php';
require dirname(__DIR__) . '/src/Api.php';

date_default_timezone_set('America/Guatemala');

// Cargar configuración del entorno
Config::load(dirname(__DIR__));

// Identificador para rastrear solicitudes y errores
$GLOBALS['request_id'] = bin2hex(random_bytes(8));

header('X-Request-ID: ' . $GLOBALS['request_id']);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

// CORS: permitir solamente el frontend configurado
$allowedOrigin = Config::get('FRONTEND_ORIGIN', '');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (
    $origin !== '' &&
    $allowedOrigin !== '' &&
    hash_equals($allowedOrigin, $origin)
) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
}

// Responder a las solicitudes preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Manejo centralizado de excepciones
set_exception_handler(function (Throwable $e): void {
    $status = 500;
    $code = 'INTERNAL_ERROR';
    $message = 'Ocurrió un error interno.';

    if ($e instanceof InvalidArgumentException) {
        $status = 422;
        $code = 'VALIDATION_ERROR';
        $message = $e->getMessage();
    } elseif ($e instanceof DomainException) {
        $status = 409;
        $code = 'CONFLICT';
        $message = $e->getMessage();
    }

    Logger::write(
        $status >= 500 ? 'error' : 'warning',
        $e->getMessage(),
        [
            'type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    );

    Http::json([
        'ok' => false,
        'error' => [
            'code' => $code,
            'message' => Config::bool('APP_DEBUG')
                ? $e->getMessage()
                : $message,
            'request_id' => $GLOBALS['request_id']
        ]
    ], $status);
});

// Ejecutar la API
App\Api::dispatch();
