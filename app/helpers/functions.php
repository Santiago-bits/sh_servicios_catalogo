<?php
/**
 * ARCHIVO: app/helpers/functions.php
 * ---------------------------------------------------------------------
 * Funciones auxiliares globales: escape, URLs, formato de moneda,
 * slugs, fechas, badges y utilidades de presentación.
 */

declare(strict_types=1);

use App\Services\SettingService;
use Core\Auth;
use Core\Csrf;
use Core\Session;

// ---------------------------------------------------------------------
// Escape / salida segura
// ---------------------------------------------------------------------

/** Escapa cualquier valor para imprimirlo en HTML (defensa anti XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * JSON seguro para imprimir DENTRO de un bloque <script>.
 *
 * Los flags JSON_HEX_* impiden que un valor pueda cerrar la etiqueta
 * (</script>) o inyectar código. No se aplica htmlspecialchars porque
 * dentro de <script> el navegador NO decodifica entidades HTML.
 */
function js(mixed $value): string
{
    return (string) json_encode(
        $value,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
}

/**
 * JSON escapado para usarlo dentro de un ATRIBUTO HTML
 * (por ejemplo data-chart='...'). Acá sí corresponde escapar entidades.
 */
function ejs(mixed $value): string
{
    return htmlspecialchars(js($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Limpia HTML proveniente del editor del panel dejando sólo etiquetas
 * seguras. Se usa para las descripciones largas de productos.
 */
function clean_html(?string $html): string
{
    if ($html === null || trim($html) === '') {
        return '';
    }

    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h3><h4><h5><span><table><thead><tbody><tr><th><td><a>';
    $clean   = strip_tags($html, $allowed);

    // Elimina atributos peligrosos (on*, javascript:, style con expression)
    $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? '';
    $clean = preg_replace('/javascript\s*:/i', '', $clean) ?? '';

    return $clean;
}

// ---------------------------------------------------------------------
// URLs
// ---------------------------------------------------------------------

function url(string $path = ''): string
{
    return BASE_URL . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function admin_url(string $path = ''): string
{
    return url('admin' . ($path === '' ? '' : '/' . ltrim($path, '/')));
}

function asset(string $path): string
{
    return ASSET_URL . '/' . ltrim($path, '/');
}

/** Ruta pública de una imagen subida, con fallback al placeholder. */
function upload_url(?string $path, string $fallback = 'img/placeholder-machine.svg'): string
{
    if ($path === null || $path === '') {
        return asset($fallback);
    }
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

function machine_url(array $product): string
{
    $category = $product['category_slug'] ?? 'maquinaria';
    return url('maquinaria/' . $category . '/' . $product['slug']);
}

function part_url(array $product): string
{
    $category = $product['category_slug'] ?? 'repuestos';
    return url('repuestos/' . $category . '/' . $product['slug']);
}

function product_url(array $product): string
{
    return ($product['type'] ?? 'machine') === 'machine' ? machine_url($product) : part_url($product);
}

/** Devuelve la URL actual agregando/reemplazando parámetros del query string. */
function query_url(array $params, array $remove = []): string
{
    $query = $_GET;
    foreach ($remove as $key) {
        unset($query[$key]);
    }
    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $path . ($query === [] ? '' : '?' . http_build_query($query));
}

function is_current(string $path): bool
{
    $current = Core\Request::uri();
    $path    = '/' . trim($path, '/');
    return $path === '/' ? $current === '/' : str_starts_with($current, $path);
}

function active(string $path, string $class = 'active'): string
{
    return is_current($path) ? $class : '';
}

// ---------------------------------------------------------------------
// Seguridad en vistas
// ---------------------------------------------------------------------

function csrf_field(): string
{
    return Csrf::field();
}

function csrf_token(): string
{
    return Csrf::token();
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function auth_user(): ?array
{
    return Auth::user();
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

function setting(string $key, mixed $default = ''): mixed
{
    return SettingService::get($key, $default);
}

// ---------------------------------------------------------------------
// Números, moneda y unidades
// ---------------------------------------------------------------------

/** Convierte "1.234.567,89" o "1234567.89" a float. */
function normalize_decimal(string $value, float $default = 0.0): float
{
    $value = trim(str_replace([' ', '$', 'USD', 'ARS'], '', $value));
    if ($value === '') {
        return $default;
    }

    $lastComma = strrpos($value, ',');
    $lastDot   = strrpos($value, '.');

    if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
        // Formato español: la coma es el separador decimal
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } else {
        $value = str_replace(',', '', $value);
    }

    return is_numeric($value) ? (float) $value : $default;
}

function money(float|int|string|null $amount, string $currency = 'ARS', int $decimals = 2): string
{
    $amount = (float) ($amount ?? 0);
    $symbol = $currency === 'USD' ? 'USD ' : '$';
    return $symbol . number_format($amount, $decimals, ',', '.');
}

function number_es(float|int|string|null $value, int $decimals = 0): string
{
    return number_format((float) ($value ?? 0), $decimals, ',', '.');
}

function percent(float|int|string|null $value, int $decimals = 1): string
{
    return number_format((float) ($value ?? 0), $decimals, ',', '.') . '%';
}

/** Convierte milímetros a una expresión legible (mm / m). */
function mm_to_human(?int $mm): string
{
    if ($mm === null || $mm === 0) {
        return '—';
    }
    return $mm >= 1000
        ? number_es($mm / 1000, 2) . ' m'
        : number_es($mm) . ' mm';
}

function kg_to_human(float|int|string|null $kg): string
{
    $kg = (float) ($kg ?? 0);
    if ($kg <= 0) {
        return '—';
    }
    return $kg >= 1000
        ? number_es($kg / 1000, $kg % 1000 === 0.0 ? 0 : 1) . ' t'
        : number_es($kg) . ' kg';
}

function hp_to_kw(float|int|null $hp): string
{
    if (!$hp) {
        return '—';
    }
    return number_es((float) $hp * 0.7457, 1) . ' kW';
}

// ---------------------------------------------------------------------
// Texto
// ---------------------------------------------------------------------

function slugify(string $text): string
{
    $text = trim($text);
    $text = strtr($text, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c','º'=>'','ª'=>'',
    ]);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Normaliza texto para búsquedas: sin acentos, minúsculas, sin dobles espacios. */
function normalize_search(string $text): string
{
    $text = strtr(mb_strtolower(trim($text), 'UTF-8'), [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
    ]);
    return preg_replace('/\s+/', ' ', $text) ?? $text;
}

function str_limit(?string $text, int $limit = 120, string $end = '…'): string
{
    $text = trim(strip_tags((string) $text));
    return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . $end;
}

/** Resalta las coincidencias de una búsqueda (recibe texto ya escapado). */
function highlight(string $escapedText, string $term): string
{
    $term = trim($term);
    if ($term === '' || mb_strlen($term) < 2) {
        return $escapedText;
    }
    return preg_replace(
        '/(' . preg_quote(e($term), '/') . ')/iu',
        '<mark>$1</mark>',
        $escapedText
    ) ?? $escapedText;
}

// ---------------------------------------------------------------------
// Fechas
// ---------------------------------------------------------------------

function date_es(?string $date, bool $withTime = false): string
{
    if ($date === null || $date === '' || str_starts_with($date, '0000')) {
        return '—';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
}

function time_ago(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }

    $diff = time() - $timestamp;

    return match (true) {
        $diff < 60      => 'hace instantes',
        $diff < 3600    => 'hace ' . (int) ($diff / 60) . ' min',
        $diff < 86400   => 'hace ' . (int) ($diff / 3600) . ' h',
        $diff < 604800  => 'hace ' . (int) ($diff / 86400) . ' d',
        default         => date_es($date),
    };
}

// ---------------------------------------------------------------------
// Presentación de estados
// ---------------------------------------------------------------------

/** @return array{label:string,class:string,icon:string} */
function availability_badge(string $availability): array
{
    return match ($availability) {
        'disponible'    => ['label' => 'Disponible',            'class' => 'ok',      'icon' => 'bi-check-circle-fill'],
        'reservada'     => ['label' => 'Reservada',             'class' => 'warn',    'icon' => 'bi-bookmark-check-fill'],
        'vendida'       => ['label' => 'Vendida',               'class' => 'off',     'icon' => 'bi-x-circle-fill'],
        'mantenimiento' => ['label' => 'En mantenimiento',      'class' => 'info',    'icon' => 'bi-tools'],
        default         => ['label' => 'Consultar disponibilidad','class' => 'neutral','icon' => 'bi-question-circle-fill'],
    };
}

/** Semáforo de stock: 🟢 🟡 🔴 ⚫ @return array{label:string,class:string,dot:string} */
function stock_badge(array $product): array
{
    $available = (int) $product['stock'] - (int) ($product['stock_reserved'] ?? 0);
    $min       = (int) ($product['stock_min'] ?? 0) ?: (int) setting('low_stock_threshold', 3);

    if (!(int) ($product['track_stock'] ?? 0)) {
        return ['label' => 'Consultar disponibilidad', 'class' => 'neutral', 'dot' => '⚫'];
    }
    if ($available <= 0) {
        return ['label' => 'Sin stock', 'class' => 'off', 'dot' => '🔴'];
    }
    if ($available <= $min) {
        return ['label' => 'Últimas unidades', 'class' => 'warn', 'dot' => '🟡'];
    }
    return ['label' => 'Disponible', 'class' => 'ok', 'dot' => '🟢'];
}

function quote_status_badge(string $status): array
{
    return match ($status) {
        'borrador'  => ['label' => 'Borrador',  'class' => 'neutral'],
        'enviada'   => ['label' => 'Enviada',   'class' => 'info'],
        'aceptada'  => ['label' => 'Aceptada',  'class' => 'ok'],
        'rechazada' => ['label' => 'Rechazada', 'class' => 'off'],
        default     => ['label' => 'Vencida',   'class' => 'warn'],
    };
}

function inquiry_status_badge(string $status): array
{
    return match ($status) {
        'nueva'      => ['label' => 'Nueva',      'class' => 'accent'],
        'en_proceso' => ['label' => 'En proceso', 'class' => 'info'],
        'respondida' => ['label' => 'Respondida', 'class' => 'ok'],
        default      => ['label' => 'Cerrada',    'class' => 'neutral'],
    };
}

function fuel_label(?string $fuel): string
{
    return match ($fuel) {
        'electrico' => 'Eléctrico',
        'diesel'    => 'Diésel',
        'nafta'     => 'Nafta',
        'gas'       => 'Gas',
        'glp'       => 'GLP',
        'hibrido'   => 'Híbrido',
        'manual'    => 'Manual',
        default     => '—',
    };
}

// ---------------------------------------------------------------------
// Varios
// ---------------------------------------------------------------------

function ip_hash(string $ip): string
{
    return hash('sha256', APP_KEY . '|' . $ip);
}

function dd(mixed ...$values): never
{
    echo '<pre style="background:#111;color:#F5C400;padding:16px;border-radius:8px;overflow:auto">';
    foreach ($values as $value) {
        var_dump($value);
    }
    echo '</pre>';
    exit;
}
