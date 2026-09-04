<?php
/**
 * ARCHIVO: app/services/ExchangeRateService.php
 * ---------------------------------------------------------------------
 * Cotización del dólar tomada automáticamente de lanacion.com.ar.
 *
 * - fetch()          → baja la página y devuelve las cotizaciones.
 * - refreshIfStale() → se llama en cada request; sólo sale a internet
 *                      cuando pasó el tiempo configurado (y nunca rompe
 *                      la página si el hosting bloquea la conexión).
 * - refreshNow()     → fuerza la actualización (botón del panel).
 *
 * El valor obtenido se guarda en la configuración (`usd_rate`) y en la
 * tabla `currencies`, así toda la conversión del sitio lo usa sin
 * cambios adicionales.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Throwable;

final class ExchangeRateService
{
    /** Página de La Nación con las cotizaciones del día. */
    private const URL = 'https://www.lanacion.com.ar/dolar-hoy/';

    /** clave interna => título tal cual aparece en el HTML de La Nación. */
    private const SOURCES = [
        'oficial' => 'Dólar oficial',
        'blue'    => 'Dólar blue',
        'mep'     => 'Dólar MEP',
        'ccl'     => 'Dólar CCL',
        'tarjeta' => 'Dólar tarjeta',
    ];

    private const CACHE_DIR   = STORAGE_PATH . '/cache';
    private const CACHE_FILE  = self::CACHE_DIR . '/usd-rate.json';
    private const ATTEMPT_FILE = self::CACHE_DIR . '/usd-rate.attempt';

    /** Si un intento falla, no se reintenta hasta pasados estos segundos. */
    private const RETRY_AFTER = 1800; // 30 min

    // -----------------------------------------------------------------
    //  Uso desde el sitio
    // -----------------------------------------------------------------

    /**
     * Se llama en cada request. Sale a internet como mucho una vez cada
     * `usd_rate_ttl_hours` horas y jamás lanza excepciones.
     */
    public static function refreshIfStale(): void
    {
        try {
            // No se sale a internet en el camino de una API ni de un POST:
            // ahí un lanacion.com.ar lento sería un cuello de botella.
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            if ($method !== 'GET' || str_starts_with(\Core\Request::uri(), '/api')) {
                return;
            }

            if (!SettingService::bool('usd_rate_auto', false)) {
                return;
            }

            $ttl = max(1, SettingService::int('usd_rate_ttl_hours', 12)) * 3600;

            // ¿La última actualización exitosa sigue vigente?
            $lastOk = is_file(self::CACHE_FILE) ? (int) @filemtime(self::CACHE_FILE) : 0;
            if ($lastOk > 0 && (time() - $lastOk) < $ttl) {
                return;
            }

            // ¿Hubo un intento (fallido) hace muy poco? No insistir.
            $lastTry = is_file(self::ATTEMPT_FILE) ? (int) @filemtime(self::ATTEMPT_FILE) : 0;
            if ($lastTry > 0 && (time() - $lastTry) < self::RETRY_AFTER) {
                return;
            }

            self::touchAttempt();
            self::refresh(SettingService::get('usd_rate_source', 'blue'));
        } catch (Throwable $e) {
            error_log('[USD] refreshIfStale: ' . $e->getMessage());
        }
    }

    /**
     * Fuerza la actualización ahora (botón del panel). No propaga
     * excepciones: devuelve el resultado para mostrarlo.
     *
     * @return array{ok:bool,rate:float,source:string,message:string}
     */
    public static function refreshNow(?string $source = null): array
    {
        $source = $source ?: (string) SettingService::get('usd_rate_source', 'blue');

        try {
            self::touchAttempt();
            return self::refresh($source);
        } catch (Throwable $e) {
            error_log('[USD] refreshNow: ' . $e->getMessage());
            return ['ok' => false, 'rate' => 0.0, 'source' => $source, 'message' => $e->getMessage()];
        }
    }

    /** Última cotización guardada (para mostrar en el panel). @return array<string,mixed>|null */
    public static function cached(): ?array
    {
        if (!is_file(self::CACHE_FILE)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents(self::CACHE_FILE), true);
        return is_array($data) ? $data : null;
    }

    /** @return array<string,string> clave => etiqueta legible */
    public static function sourceLabels(): array
    {
        return [
            'oficial' => 'Oficial',
            'blue'    => 'Blue',
            'mep'     => 'MEP',
            'ccl'     => 'Contado con liqui (CCL)',
            'tarjeta' => 'Tarjeta / turista',
        ];
    }

    // -----------------------------------------------------------------
    //  Interno
    // -----------------------------------------------------------------

    /**
     * Baja la página, elige la cotización pedida, la guarda y devuelve
     * el resultado.
     *
     * @return array{ok:bool,rate:float,source:string,message:string}
     */
    private static function refresh(string $source): array
    {
        if (!isset(self::SOURCES[$source])) {
            $source = 'blue';
        }

        [$status, $html] = self::httpGet(self::URL);

        if ($status !== 200 || $html === '') {
            return [
                'ok' => false, 'rate' => 0.0, 'source' => $source,
                'message' => $status === 0
                    ? 'No se pudo conectar con lanacion.com.ar (puede estar bloqueado por el hosting).'
                    : 'lanacion.com.ar respondió con el código ' . $status . '.',
            ];
        }

        $all  = self::parse($html);
        $rate = $all[$source]['venta'] ?? $all[$source]['compra'] ?? 0.0;

        if ($rate <= 0) {
            return [
                'ok' => false, 'rate' => 0.0, 'source' => $source,
                'message' => 'Se bajó la página pero no se pudo leer el valor del dólar ' . $source
                    . ' (puede haber cambiado el formato de La Nación).',
            ];
        }

        // Persistir: configuración + tabla currencies + caché en disco.
        SettingService::set('usd_rate', self::numberString($rate));
        (new Setting())->updateRate('USD', $rate);
        CurrencyService::flush();

        self::writeCache([
            'rate'    => $rate,
            'source'  => $source,
            'at'      => date('c'),
            'all'     => $all,
        ]);

        return [
            'ok' => true, 'rate' => $rate, 'source' => $source,
            'message' => 'Dólar ' . $source . ' actualizado: $' . self::numberString($rate) . '.',
        ];
    }

    /**
     * Extrae todas las cotizaciones del HTML de La Nación.
     *
     * @return array<string,array{compra?:float,venta?:float}>
     */
    public static function parse(string $html): array
    {
        $out = [];

        foreach (self::SOURCES as $key => $title) {
            // Bloque de datos de esa moneda: <a title="Dólar X" class="... link-container-currency-data"> ... <p>...</p>
            $re = '#title="' . preg_quote($title, '#')
                . '"\s+class="com-link link-container-currency-data".*?<p\b[^>]*>(.*?)</p>#su';

            if (!preg_match($re, $html, $m)) {
                continue;
            }
            $block = $m[1];

            $values = [];
            if (preg_match('#Compra</span>\s*<strong[^>]*>\$(?:<!--\s*-->)?\s*([\d.]+,\d{2})#u', $block, $c)) {
                $values['compra'] = self::toFloat($c[1]);
            }
            if (preg_match('#Venta</span>\s*<strong[^>]*>\$(?:<!--\s*-->)?\s*([\d.]+,\d{2})#u', $block, $v)) {
                $values['venta'] = self::toFloat($v[1]);
            }

            if ($values !== []) {
                $out[$key] = $values;
            }
        }

        return $out;
    }

    /** "1.555,00" (formato argentino) => 1555.00 */
    private static function toFloat(string $n): float
    {
        return (float) str_replace(',', '.', str_replace('.', '', $n));
    }

    /** Float a string sin notación científica ni ceros de más. */
    private static function numberString(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    /**
     * GET con cURL y, si no está disponible, con streams.
     *
     * @return array{0:int,1:string}
     */
    private static function httpGet(string $url): array
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/124.0 Safari/537.36';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => $ua,
                CURLOPT_HTTPHEADER     => ['Accept: text/html', 'Accept-Language: es-AR,es;q=0.9'],
            ]);
            $body   = (string) curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [$status, $body];
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'header'        => "User-Agent: {$ua}\r\nAccept: text/html\r\nAccept-Language: es-AR,es;q=0.9\r\n",
                'timeout'       => 15,
                'follow_location'=> 1,
                'max_redirects' => 3,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $body   = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $mm)) {
                $status = (int) $mm[1];
            }
        }

        return [$status, $body === false ? '' : $body];
    }

    private static function ensureDir(): void
    {
        if (!is_dir(self::CACHE_DIR)) {
            @mkdir(self::CACHE_DIR, 0775, true);
        }
    }

    private static function touchAttempt(): void
    {
        self::ensureDir();
        @touch(self::ATTEMPT_FILE);
    }

    /** @param array<string,mixed> $data */
    private static function writeCache(array $data): void
    {
        self::ensureDir();
        @file_put_contents(self::CACHE_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
