<?php
declare(strict_types=1);
namespace App;

final class Logger {
    public static function write(string $level, string $message, array $context = []): void {
        $dir = dirname(__DIR__) . '/' . (Config::get('LOG_PATH', 'storage/logs') ?? 'storage/logs');
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $entry = [
            'time' => date(DATE_ATOM),
            'level' => strtoupper($level),
            'message' => $message,
            'request_id' => $GLOBALS['request_id'] ?? null,
            'context' => self::sanitize($context),
        ];
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($line !== false) {
            $file = $dir . '/' . date('Y-m-d') . '.log';
            if (@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
                error_log($line);
            }
        }
    }

    private static function sanitize(array $data): array {
        foreach ($data as $key => $value) {
            if (preg_match('/password|token|secret|authorization|cookie/i', (string)$key)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (is_object($value)) {
                $data[$key] = '[OBJECT]';
            }
        }
        return $data;
    }
}
