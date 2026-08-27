<?php
/**
 * ARCHIVO: core/Env.php
 * ---------------------------------------------------------------------
 * Lector minimalista de archivos .env (sin dependencias externas).
 */

declare(strict_types=1);

namespace Core;

final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_readable($file)) {
            // Sin .env el sistema sigue funcionando con los valores por defecto.
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Quitar comillas envolventes
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            self::$vars[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$vars[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }

    /** @return array<string,string> */
    public static function all(): array
    {
        return self::$vars;
    }
}
