<?php
/**
 * ARCHIVO: core/Request.php
 * ---------------------------------------------------------------------
 * Acceso saneado a la petición HTTP. Nunca se usa $_GET/$_POST directo
 * en controladores: todo pasa por acá.
 */

declare(strict_types=1);

namespace Core;

final class Request
{
    public static function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Soporte para _method en formularios (PUT/PATCH/DELETE)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper((string) $_POST['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofed;
            }
        }

        return $method;
    }

    public static function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);

        $base = self::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    /**
     * Subdirectorio donde está instalada la aplicación.
     *
     * El proyecto ya no usa la subcarpeta /public: la raíz del proyecto
     * es también la raíz web. Tiene que funcionar en tres escenarios:
     *
     *  1. Proyecto en la raíz del dominio (InfinityFree, VPS con el
     *     DocumentRoot en esta carpeta, etc.)
     *     SCRIPT_NAME=/index.php · REQUEST_URI=/maquinaria         → ''
     *
     *  2. XAMPP en un subdirectorio
     *     SCRIPT_NAME=/sh-servicios/index.php
     *     REQUEST_URI=/sh-servicios/maquinaria         → '/sh-servicios'
     *
     *  3. Servidor embebido de PHP con server.php
     *     SCRIPT_NAME=/maquinaria (no termina en .php)             → ''
     *
     * La clave del caso 2 es que, cuando hay reescritura interna, la URL
     * pedida NO empieza con la carpeta del script: ahí hay que subir un
     * nivel. (El código conserva también el salto de dos niveles por si
     * algún hosting sirviera todavía desde una subcarpeta /public.)
     */
    public static function basePath(): string
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        // Caso 4: el servidor embebido pone la URL pedida en SCRIPT_NAME
        if ($script === '' || !str_ends_with($script, '.php')) {
            return $cached = '';
        }

        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($dir === '' || $dir === '.') {
            return $cached = '';               // caso 1
        }

        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

        // Se llegó directamente por esa carpeta (sin reescritura)
        if (str_starts_with($uri, $dir . '/') || $uri === $dir) {
            return $cached = $dir;
        }

        // Hubo reescritura interna: la base real es la carpeta de arriba
        $parent = rtrim(str_replace('\\', '/', dirname($dir)), '/');

        if ($parent !== '' && $parent !== '.' && (str_starts_with($uri, $parent . '/') || $uri === $parent)) {
            return $cached = $parent;          // caso 3
        }

        return $cached = '';                   // caso 2
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::clean($_GET[$key] ?? $default);
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return self::clean($_POST[$key] ?? $default);
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return self::clean($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        if ($value === null || $value === '' || is_array($value)) {
            return $default;
        }
        return (int) $value;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        if ($value === null || $value === '' || is_array($value)) {
            return $default;
        }
        // Acepta "1.234.567,89" y "1234567.89"
        return normalize_decimal((string) $value, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes', 'si'], true);
    }

    /** @return array<int,string> */
    public static function array(string $key): array
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? [];
        if (!is_array($value)) {
            return $value === '' || $value === null ? [] : [self::clean($value)];
        }
        return array_map(static fn ($v) => is_array($v) ? '' : self::clean($v), $value);
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        $data = array_merge($_GET, $_POST);
        unset($data['_token'], $data['_method']);
        return array_map(static fn ($v) => is_array($v) ? $v : self::clean($v), $data);
    }

    /** @return array<string,mixed>|null Cuerpo JSON de la petición. */
    public static function json(): ?array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    public static function file(string $key): ?array
    {
        return isset($_FILES[$key]) && ($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            ? $_FILES[$key]
            : null;
    }

    /** @return array<int,array<string,mixed>> Normaliza inputs file[] múltiples. */
    public static function files(string $key): array
    {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key]['name'])) {
            $single = self::file($key);
            return $single ? [$single] : [];
        }

        $out   = [];
        $files = $_FILES[$key];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }

        return $out;
    }

    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string) $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
    }

    public static function referer(): string
    {
        return mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 250);
    }

    public static function url(): string
    {
        return BASE_URL . self::uri();
    }

    public static function device(): string
    {
        $ua = strtolower(self::userAgent());
        return match (true) {
            $ua === ''                                          => 'otro',
            (bool) preg_match('/bot|crawl|spider|slurp/', $ua)   => 'bot',
            (bool) preg_match('/ipad|tablet/', $ua)              => 'tablet',
            (bool) preg_match('/mobile|android|iphone/', $ua)    => 'mobile',
            default                                             => 'desktop',
        };
    }

    private static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            // Se eliminan bytes nulos y espacios; el escape para HTML se
            // hace SIEMPRE al imprimir (helper e()), no acá.
            return trim(str_replace("\0", '', $value));
        }
        return $value;
    }
}
