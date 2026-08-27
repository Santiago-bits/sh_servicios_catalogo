<?php
/**
 * ARCHIVO: app/services/SettingService.php
 * ---------------------------------------------------------------------
 * Acceso cacheado a la configuración del sitio. Todo lo que podría
 * estar hardcodeado (WhatsApp, emails, textos, cotización del dólar)
 * vive en la base de datos y se lee desde acá.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

final class SettingService
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** @return array<string,string> */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = (new Setting())->pairs();
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        $value = self::all()[$key] ?? null;
        return $value === null || $value === '' ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::all()[$key] ?? null;
        return $value === null || $value === '' ? $default : $value === '1';
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::all()[$key] ?? null;
        return $value === null || $value === '' ? $default : (int) $value;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::all()[$key] ?? null;
        return $value === null || $value === '' ? $default : (float) $value;
    }

    public static function set(string $key, string $value): void
    {
        (new Setting())->put($key, $value);
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    // ----------------------------------------------------------------
    // Accesos frecuentes
    // ----------------------------------------------------------------

    public static function companyName(): string
    {
        return (string) self::get('company_name', 'SH Servicios');
    }

    public static function whatsapp(): string
    {
        return preg_replace('/\D+/', '', (string) self::get('contact_whatsapp', '')) ?? '';
    }

    public static function email(): string
    {
        return (string) self::get('contact_email', '');
    }

    public static function siteUrl(): string
    {
        $url = (string) self::get('site_url', '');
        return $url !== '' ? rtrim($url, '/') : BASE_URL;
    }

    public static function perPage(): int
    {
        return max(4, min(48, self::int('items_per_page', 12)));
    }

    public static function showPrices(): bool
    {
        return self::bool('show_prices_public', true);
    }
}
