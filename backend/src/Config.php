<?php
declare(strict_types=1);
namespace App;

final class Config {
    private static array $values = [];

    public static function load(string $root): void {
        $file = $root . '/.env';
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            if ($key !== '') self::$values[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string {
        if (array_key_exists($key, self::$values)) return self::$values[$key];
        $env = getenv($key);
        return $env !== false ? (string)$env : $default;
    }

    public static function bool(string $key, bool $default = false): bool {
        return filter_var(self::get($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOL);
    }
}
