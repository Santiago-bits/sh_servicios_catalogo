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

/** Nonce de la CSP para autorizar un <script> inline propio. */
function csp_nonce(): string
{
    return defined('CSP_NONCE') ? CSP_NONCE : '';
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
 * Limpia HTML proveniente del editor del panel dejando sólo una lista
 * blanca de etiquetas Y de atributos. Se usa para las descripciones
 * largas de productos y servicios.
 *
 * A diferencia de strip_tags(), acá se recorre el árbol DOM y se borra
 * cualquier atributo que no esté explícitamente permitido, y los href
 * se limitan a http(s)/mailto/tel/rutas internas. Eso cierra los vectores
 * clásicos de XSS almacenado (on*=, javascript:, data:, style, etc.).
 */
/**
 * Convierte texto plano (el que se escribe en un <textarea> con Enter y
 * líneas en blanco) a HTML: líneas en blanco → párrafos <p>, saltos
 * simples → <br>, y una lista de renglones que empiezan con "-", "*" o
 * "•" → <ul><li>. Si el texto ya trae HTML de bloque, se deja igual.
 */
function text_to_html(?string $text): string
{
    $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $text));
    if ($text === '') {
        return '';
    }
    if (preg_match('~<(p|br|ul|ol|li|h[1-6]|table|div)\b~i', $text)) {
        return $text; // ya es HTML
    }

    $out  = '';
    $para = [];   // renglones de un párrafo en curso
    $list = [];   // <li> de una lista en curso

    $flush = static function () use (&$out, &$para, &$list): void {
        if ($para !== []) {
            $out .= '<p>' . implode('<br>', array_map('e', $para)) . '</p>';
            $para = [];
        }
        if ($list !== []) {
            $out .= '<ul>' . implode('', array_map(static fn ($li) => '<li>' . e($li) . '</li>', $list)) . '</ul>';
            $list = [];
        }
    };

    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            $flush();
            continue;
        }
        if (preg_match('/^[-*•–]\s+(\S.*)$/u', $line, $m)) {
            if ($para !== []) {
                $out .= '<p>' . implode('<br>', array_map('e', $para)) . '</p>';
                $para = [];
            }
            $list[] = $m[1];
        } else {
            if ($list !== []) {
                $out .= '<ul>' . implode('', array_map(static fn ($li) => '<li>' . e($li) . '</li>', $list)) . '</ul>';
                $list = [];
            }
            $para[] = $line;
        }
    }
    $flush();

    return $out;
}

function clean_html(?string $html): string
{
    if ($html === null || trim($html) === '') {
        return '';
    }

    $allowedTags  = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li',
                     'h3', 'h4', 'h5', 'span', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'a'];
    $allowedAttrs = [
        'a'  => ['href'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    // Primer filtro por etiqueta.
    $html = strip_tags($html, '<' . implode('><', $allowedTags) . '>');

    // Sin DOM disponible (muy raro): al menos se quitan atributos on* y
    // los esquemas peligrosos con regex, como respaldo.
    if (!class_exists('DOMDocument') || !str_contains($html, '<')) {
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/(javascript|vbscript|data)\s*:/i', '', $html) ?? '';
        return trim($html);
    }

    $dom  = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="clean-html-root">' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $dom->getElementById('clean-html-root');
    if ($root === null) {
        return '';
    }

    foreach (iterator_to_array($root->getElementsByTagName('*')) as $el) {
        $tag = strtolower($el->nodeName);
        $ok  = $allowedAttrs[$tag] ?? [];

        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $name = strtolower($attr->nodeName);

            if (!in_array($name, $ok, true)) {
                $el->removeAttribute($attr->nodeName);
                continue;
            }

            if ($name === 'href') {
                $href = trim((string) $attr->nodeValue);
                // http(s), mailto, tel o ruta interna de un solo "/"
                // (se rechaza "//host" protocol-relative).
                if (!preg_match('#^(https?://|mailto:|tel:|/(?!/))#i', $href)) {
                    $el->removeAttribute('href');
                } else {
                    $el->setAttribute('rel', 'noopener nofollow');
                    $el->setAttribute('target', '_blank');
                }
            }
        }
    }

    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $dom->saveHTML($child);
    }

    return trim($out);
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
    $path = ltrim($path, '/');
    $url  = ASSET_URL . '/' . $path;

    // Cache-busting: para los assets propios (CSS/JS y también el favicon /
    // logos) se agrega ?v=<hash del contenido>, así el navegador baja la
    // versión nueva apenas cambia el archivo.
    if (preg_match('/\.(css|js|svg|png|ico|webp)$/', $path)) {
        $file = PUBLIC_PATH . '/assets/' . $path;
        if (is_file($file)) {
            $url .= '?v=' . hash('crc32b', (string) file_get_contents($file));
        }
    }

    return $url;
}

/**
 * Ícono de Bootstrap Icons como SVG embebido (no depende de la fuente,
 * se ve siempre). Sólo el set que se usa en el sitio.
 */
function bs_icon(string $name, int $size = 20): string
{
    static $paths = [
        'whatsapp'       => 'M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.591 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.104-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z',
        'telephone-fill' => 'M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z',
        'envelope-fill'  => 'M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.026A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z',
        'geo-alt-fill'   => 'M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z',
        'clock-fill'     => 'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z',
    ];

    $d = $paths[$name] ?? $paths['geo-alt-fill'];

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size
        . '" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="' . $d . '"/></svg>';
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

/**
 * URL de la foto de un producto. Cuando no tiene imagen cargada usa el
 * placeholder que corresponde al tipo (máquina o repuesto).
 *
 * @param array<string,mixed> $product
 */
function product_image_url(array $product, bool $preferThumb = true): string
{
    $path = $preferThumb
        ? ($product['thumb'] ?? $product['image'] ?? null)
        : ($product['image'] ?? $product['thumb'] ?? null);

    $fallback = ($product['type'] ?? 'machine') === 'spare_part'
        ? 'img/placeholder-part.svg'
        : 'img/placeholder-machine.svg';

    return upload_url($path !== null && $path !== '' ? (string) $path : null, $fallback);
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
