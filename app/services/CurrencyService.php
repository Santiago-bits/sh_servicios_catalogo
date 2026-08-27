<?php
/**
 * ARCHIVO: app/services/CurrencyService.php
 * ---------------------------------------------------------------------
 * Conversión multimoneda. La cotización SIEMPRE se lee de la base de
 * datos (tabla currencies / configuración), nunca de una constante
 * escrita en el código.
 */

declare(strict_types=1);

namespace App\Services;

use Core\Database;

final class CurrencyService
{
    /** @var array<string,array<string,mixed>>|null */
    private static ?array $cache = null;

    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        if (self::$cache === null) {
            $rows = Database::select('SELECT * FROM currencies WHERE active = 1');
            self::$cache = [];
            foreach ($rows as $row) {
                self::$cache[$row['code']] = $row;
            }
        }
        return self::$cache;
    }

    public static function base(): string
    {
        foreach (self::all() as $code => $currency) {
            if ((int) $currency['is_base'] === 1) {
                return $code;
            }
        }
        return (string) SettingService::get('base_currency', 'ARS');
    }

    /** Cuántas unidades de la moneda base equivalen a 1 de $code. */
    public static function rate(string $code): float
    {
        if ($code === self::base()) {
            return 1.0;
        }

        // La cotización del dólar de la configuración manda sobre la tabla.
        if ($code === 'USD') {
            $configured = SettingService::float('usd_rate', 0.0);
            if ($configured > 0) {
                return $configured;
            }
        }

        return (float) (self::all()[$code]['rate_to_base'] ?? 1.0);
    }

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to || $amount === 0.0) {
            return $amount;
        }

        $inBase = $amount * self::rate($from);

        $toRate = self::rate($to);
        if ($toRate <= 0) {
            return $inBase;
        }

        return round($inBase / $toRate, 2);
    }

    public static function symbol(string $code): string
    {
        return (string) (self::all()[$code]['symbol'] ?? '$');
    }

    /** Ambas monedas en un solo texto: "$20.000.000 · USD 20.000". */
    public static function dual(float $amount, string $currency): string
    {
        $other     = $currency === 'ARS' ? 'USD' : 'ARS';
        $converted = self::convert($amount, $currency, $other);

        return money($amount, $currency) . ' · ' . money($converted, $other, $other === 'USD' ? 0 : 2);
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
