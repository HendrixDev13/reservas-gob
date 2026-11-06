<?php
declare(strict_types=1);
namespace App;

final class Http {
    public static function json(array $body, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function input(): array {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') return [];
        $data = json_decode($raw, true);
        if (!is_array($data)) throw new \InvalidArgumentException('El cuerpo debe ser JSON válido.');
        return $data;
    }

    public static function bearer(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        return preg_match('/^Bearer\s+(.+)$/i', trim($header), $m) ? trim($m[1]) : null;
    }

    public static function id(string $value): int {
        if (!ctype_digit($value) || (int)$value < 1) throw new \InvalidArgumentException('Identificador inválido.');
        return (int)$value;
    }
}
