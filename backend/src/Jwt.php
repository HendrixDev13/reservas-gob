<?php
declare(strict_types=1);
namespace App;

/**
 * Minimal JWT HS256 implementation using PHP's hash_hmac.
 * Verifies structure, algorithm, signature, exp and nbf. Keep JWT_SECRET private.
 */
final class Jwt {
    public static function encode(array $claims, string $secret): string {
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET debe tener al menos 32 caracteres.');
        }
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $head = self::base64url((string)json_encode($header, JSON_UNESCAPED_SLASHES));
        $body = self::base64url((string)json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $signingInput = $head . '.' . $body;
        $signature = self::base64url(hash_hmac('sha256', $signingInput, $secret, true));
        return $signingInput . '.' . $signature;
    }

    public static function decode(string $token, string $secret): array {
        if (strlen($secret) < 32) throw new \RuntimeException('JWT_SECRET no está configurado de forma segura.');
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new \UnexpectedValueException('Formato JWT inválido.');

        $header = json_decode(self::unbase64url($parts[0]), true);
        $claims = json_decode(self::unbase64url($parts[1]), true);
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256' || !is_array($claims)) {
            throw new \UnexpectedValueException('Cabecera o contenido JWT inválido.');
        }

        $expected = self::base64url(hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret, true));
        if (!hash_equals($expected, $parts[2])) throw new \UnexpectedValueException('Firma JWT inválida.');

        $now = time();
        if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int)$claims['exp'] <= $now) {
            throw new \UnexpectedValueException('Token expirado o sin expiración.');
        }
        if (isset($claims['nbf']) && (!is_numeric($claims['nbf']) || (int)$claims['nbf'] > $now)) {
            throw new \UnexpectedValueException('Token aún no válido.');
        }
        return $claims;
    }

    private static function base64url(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function unbase64url(string $value): string {
        if ($value === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            throw new \UnexpectedValueException('Codificación JWT inválida.');
        }
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false) throw new \UnexpectedValueException('Codificación JWT inválida.');
        return $decoded;
    }
}
