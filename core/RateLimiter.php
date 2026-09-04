<?php
/**
 * ARCHIVO: core/RateLimiter.php
 * ---------------------------------------------------------------------
 * Límite de intentos por clave (IP, email, etc.) con ventana móvil.
 * Guarda un contador por archivo en storage/cache/throttle/. No necesita
 * base de datos ni extensiones extra.
 */

declare(strict_types=1);

namespace Core;

final class RateLimiter
{
    private static function dir(): string
    {
        $dir = STORAGE_PATH . '/cache/throttle';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . hash('sha256', $key) . '.json';
    }

    /**
     * ¿La clave superó el máximo de intentos dentro de la ventana?
     * No cuenta el intento actual (para eso está hit()).
     */
    public static function tooMany(string $key, int $max, int $windowSeconds): bool
    {
        return self::count($key, $windowSeconds) >= $max;
    }

    /** Registra un intento y devuelve cuántos hay en la ventana. */
    public static function hit(string $key, int $windowSeconds): int
    {
        $file  = self::file($key);
        $now   = time();
        $hits  = self::read($file);
        $hits  = array_values(array_filter($hits, static fn (int $t): bool => $t > $now - $windowSeconds));
        $hits[] = $now;

        @file_put_contents($file, json_encode($hits), LOCK_EX);

        return count($hits);
    }

    /** Limpia el contador de una clave (ej. tras un login exitoso). */
    public static function clear(string $key): void
    {
        @unlink(self::file($key));
    }

    private static function count(string $key, int $windowSeconds): int
    {
        $now  = time();
        $hits = self::read(self::file($key));
        return count(array_filter($hits, static fn (int $t): bool => $t > $now - $windowSeconds));
    }

    /** @return array<int,int> */
    private static function read(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        return is_array($data) ? array_map('intval', $data) : [];
    }
}
