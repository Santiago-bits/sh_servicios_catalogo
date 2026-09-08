<?php
/**
 * ARCHIVO: app/services/PriceService.php
 * ---------------------------------------------------------------------
 * Cálculo de precios y control de qué información de precio puede ver
 * cada tipo de usuario.
 *
 *   Costo + Ganancia (%) = Precio final
 *   Ganancia ($) = Costo × (Ganancia % / 100)
 *
 * El costo y la ganancia son información interna: JAMÁS se envían al
 * sitio público. La decisión se toma en el backend (ver publicView()).
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceHistory;
use Core\Auth;
use Core\Database;

final class PriceService
{
    /**
     * Calcula los tres valores derivados a partir de dos de ellos.
     *
     * @return array{cost_price:float,profit_percent:float,profit_amount:float,final_price:float}
     */
    public static function calculate(float $cost, float $profitPercent = 0.0, ?float $finalPrice = null): array
    {
        $cost = max(0.0, $cost);

        // Si viene el precio final y no el porcentaje, se deduce la ganancia.
        if ($finalPrice !== null && $finalPrice > 0 && $profitPercent <= 0 && $cost > 0) {
            $profitAmount  = $finalPrice - $cost;
            $profitPercent = $cost > 0 ? ($profitAmount / $cost) * 100 : 0.0;

            return [
                'cost_price'     => round($cost, 2),
                'profit_percent' => round($profitPercent, 3),
                'profit_amount'  => round($profitAmount, 2),
                'final_price'    => round($finalPrice, 2),
            ];
        }

        $profitPercent = max(0.0, $profitPercent);
        $profitAmount  = $cost * ($profitPercent / 100);
        $final         = $finalPrice !== null && $finalPrice > 0 && $cost <= 0
            ? $finalPrice
            : $cost + $profitAmount;

        return [
            'cost_price'     => round($cost, 2),
            'profit_percent' => round($profitPercent, 3),
            'profit_amount'  => round($profitAmount, 2),
            'final_price'    => round($final, 2),
        ];
    }

    /** Margen sobre venta (distinto del markup sobre costo). */
    public static function margin(float $cost, float $finalPrice): float
    {
        if ($finalPrice <= 0) {
            return 0.0;
        }
        return round((($finalPrice - $cost) / $finalPrice) * 100, 2);
    }

    /**
     * Aplica un cambio de precio y deja registro en el historial.
     *
     * @param array<string,mixed> $product Estado actual del producto
     * @return array{cost_price:float,profit_percent:float,profit_amount:float,final_price:float}
     */
    public static function applyChange(array $product, float $cost, float $profitPercent, ?float $finalPrice, string $reason = ''): array
    {
        $new = self::calculate($cost, $profitPercent, $finalPrice);

        $changed = (float) $product['cost_price'] !== $new['cost_price']
            || (float) $product['profit_percent'] !== $new['profit_percent']
            || (float) $product['final_price'] !== $new['final_price'];

        Database::update('products', [
            'cost_price'       => $new['cost_price'],
            'profit_percent'   => $new['profit_percent'],
            'profit_amount'    => $new['profit_amount'],
            'final_price'      => $new['final_price'],
            'price_updated_at' => date('Y-m-d H:i:s'),
            'updated_by'       => Auth::id(),
        ], 'id = :id', ['id' => (int) $product['id']]);

        if ($changed) {
            (new PriceHistory())->create([
                'product_id'         => (int) $product['id'],
                'old_cost'           => (float) $product['cost_price'],
                'new_cost'           => $new['cost_price'],
                'old_profit_percent' => (float) $product['profit_percent'],
                'new_profit_percent' => $new['profit_percent'],
                'old_profit_amount'  => (float) $product['profit_amount'],
                'new_profit_amount'  => $new['profit_amount'],
                'old_price'          => (float) $product['final_price'],
                'new_price'          => $new['final_price'],
                'currency'           => (string) ($product['currency'] ?? 'ARS'),
                'reason'             => $reason !== '' ? mb_substr($reason, 0, 255) : 'Actualización manual',
                'user_id'            => Auth::id(),
            ]);

            AuditService::log(
                'price_change',
                'prices',
                'product',
                (int) $product['id'],
                sprintf(
                    'Precio de %s: %s → %s',
                    (string) $product['code'],
                    money((float) $product['final_price'], (string) ($product['currency'] ?? 'ARS')),
                    money($new['final_price'], (string) ($product['currency'] ?? 'ARS'))
                ),
                ['motivo' => $reason]
            );
        }

        return $new;
    }

    /**
     * Aumento/descuento masivo por categoría, marca o tipo.
     *
     * @param array<string,mixed> $filters
     * @return int cantidad de productos afectados
     */
    public static function bulkAdjust(array $filters, float $percent, string $target, string $reason): int
    {
        $conditions = ['deleted_at IS NULL'];
        $params     = [];

        if (!empty($filters['tipo']) && in_array($filters['tipo'], ['machine', 'spare_part'], true)) {
            $conditions[]    = 'type = :type';
            $params['type']  = $filters['tipo'];
        }
        if (!empty($filters['categoria'])) {
            $conditions[]       = 'category_id = :cat';
            $params['cat']      = (int) $filters['categoria'];
        }
        if (!empty($filters['marca'])) {
            $conditions[]       = 'brand_id = :brand';
            $params['brand']    = (int) $filters['marca'];
        }

        $products = Database::select(
            'SELECT * FROM products WHERE ' . implode(' AND ', $conditions),
            $params
        );

        $factor  = 1 + ($percent / 100);
        $updated = 0;

        Database::beginTransaction();

        try {
            foreach ($products as $product) {
                if ($target === 'costo') {
                    $newCost = round((float) $product['cost_price'] * $factor, 2);
                    self::applyChange($product, $newCost, (float) $product['profit_percent'], null, $reason);
                } else {
                    $newPrice = round((float) $product['final_price'] * $factor, 2);
                    // Se recalcula la ganancia para mantener la coherencia del costo
                    self::applyChange($product, (float) $product['cost_price'], 0.0, $newPrice, $reason);
                }
                $updated++;
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        AuditService::log(
            'price_bulk',
            'prices',
            null,
            null,
            sprintf('Ajuste masivo de %s%% sobre %s (%d productos)', number_es($percent, 2), $target, $updated),
            ['filtros' => $filters, 'motivo' => $reason]
        );

        return $updated;
    }

    // ----------------------------------------------------------------
    // Visibilidad
    // ----------------------------------------------------------------

    /**
     * Quita del array cualquier dato interno antes de mandarlo al
     * navegador. Se usa en todas las respuestas JSON públicas.
     *
     * @param array<string,mixed> $product
     * @return array<string,mixed>
     */
    public static function publicView(array $product): array
    {
        unset(
            $product['cost_price'],
            $product['profit_percent'],
            $product['profit_amount'],
            $product['price_updated_at'],
            $product['created_by'],
            $product['updated_by'],
            $product['serial_number']
        );

        return $product;
    }

    /** ¿Se puede mostrar el precio de este producto en el sitio público? */
    public static function isPublicPriceVisible(array $product): bool
    {
        return SettingService::showPrices()
            && (int) ($product['price_visible'] ?? 1) === 1
            && (float) ($product['final_price'] ?? 0) > 0;
    }

    /** Precio efectivo de venta (contempla oferta vigente). */
    public static function effectivePrice(array $product): float
    {
        $offer = (float) ($product['offer_price'] ?? 0);
        if ((int) ($product['is_offer'] ?? 0) === 1 && $offer > 0) {
            return $offer;
        }
        return (float) ($product['final_price'] ?? 0);
    }

    /** Texto listo para mostrar en el catálogo público. */
    public static function displayPrice(array $product): string
    {
        if (!self::isPublicPriceVisible($product)) {
            return 'Consultar precio';
        }

        $currency = (string) ($product['currency'] ?? 'ARS');
        $price    = self::effectivePrice($product);
        $mode     = (string) SettingService::get('price_display_mode', 'both');

        if ($mode === 'both' && SettingService::bool('show_dual_currency', false)) {
            $converted = CurrencyService::convert($price, $currency, $currency === 'ARS' ? 'USD' : 'ARS');
            return money($price, $currency) . ' · ' . money($converted, $currency === 'ARS' ? 'USD' : 'ARS', 0);
        }

        return money($price, $currency);
    }
}
