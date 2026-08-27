<?php
/**
 * ARCHIVO: app/services/StockService.php
 * ---------------------------------------------------------------------
 * Movimientos de stock con historial. Todo cambio de existencias pasa
 * por acá: nunca se hace un UPDATE suelto sobre products.stock.
 */

declare(strict_types=1);

namespace App\Services;

use Core\Auth;
use Core\Database;
use RuntimeException;

final class StockService
{
    public const TYPES = [
        'entrada'    => 'Entrada',
        'salida'     => 'Salida',
        'reserva'    => 'Reserva',
        'liberacion' => 'Liberación de reserva',
        'ajuste'     => 'Ajuste de inventario',
        'venta'      => 'Venta',
    ];

    /**
     * Registra un movimiento y actualiza el stock del producto.
     *
     * @return array{ok:bool,message:string,stock?:int}
     */
    public static function move(int $productId, string $type, int $quantity, string $reason = '', ?string $reference = null): array
    {
        if (!isset(self::TYPES[$type])) {
            return ['ok' => false, 'message' => 'Tipo de movimiento inválido.'];
        }

        $quantity = abs($quantity);
        if ($quantity === 0 && $type !== 'ajuste') {
            return ['ok' => false, 'message' => 'La cantidad debe ser mayor a cero.'];
        }

        try {
            return Database::transaction(static function () use ($productId, $type, $quantity, $reason, $reference): array {
                // Bloqueo de fila para evitar condiciones de carrera
                $product = Database::selectOne(
                    'SELECT id, code, name, stock, stock_reserved, stock_min FROM products WHERE id = :id FOR UPDATE',
                    ['id' => $productId]
                );

                if ($product === null) {
                    throw new RuntimeException('El producto no existe.');
                }

                $stock    = (int) $product['stock'];
                $reserved = (int) $product['stock_reserved'];
                $newStock = $stock;
                $newReserved = $reserved;

                switch ($type) {
                    case 'entrada':
                        $newStock = $stock + $quantity;
                        break;

                    case 'salida':
                    case 'venta':
                        if ($quantity > ($stock - $reserved)) {
                            throw new RuntimeException(
                                sprintf('No hay stock suficiente. Disponible: %d unidad(es).', max(0, $stock - $reserved))
                            );
                        }
                        $newStock = $stock - $quantity;
                        break;

                    case 'reserva':
                        if ($quantity > ($stock - $reserved)) {
                            throw new RuntimeException('No hay stock libre para reservar.');
                        }
                        $newReserved = $reserved + $quantity;
                        break;

                    case 'liberacion':
                        $newReserved = max(0, $reserved - $quantity);
                        break;

                    case 'ajuste':
                        // En un ajuste, la cantidad es el stock resultante.
                        $newStock = $quantity;
                        break;
                }

                Database::update('products', [
                    'stock'          => $newStock,
                    'stock_reserved' => $newReserved,
                ], 'id = :id', ['id' => $productId]);

                Database::insert('stock_movements', [
                    'product_id'   => $productId,
                    'type'         => $type,
                    'quantity'     => $quantity,
                    'stock_before' => $stock,
                    'stock_after'  => $newStock,
                    'reason'       => $reason !== '' ? mb_substr($reason, 0, 255) : self::TYPES[$type],
                    'reference'    => $reference !== null && $reference !== '' ? mb_substr($reference, 0, 80) : null,
                    'user_id'      => Auth::id(),
                ]);

                AuditService::log(
                    'stock_change',
                    'stock',
                    'product',
                    $productId,
                    sprintf('%s de %d unidad(es) en %s (%d → %d)', self::TYPES[$type], $quantity, (string) $product['code'], $stock, $newStock),
                    ['motivo' => $reason, 'referencia' => $reference]
                );

                return ['ok' => true, 'message' => 'Movimiento registrado.', 'stock' => $newStock];
            });
        } catch (RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** Unidades realmente disponibles para vender. */
    public static function available(array $product): int
    {
        return max(0, (int) ($product['stock'] ?? 0) - (int) ($product['stock_reserved'] ?? 0));
    }

    public static function isLow(array $product): bool
    {
        if ((int) ($product['track_stock'] ?? 0) !== 1) {
            return false;
        }
        $min = (int) ($product['stock_min'] ?? 0) ?: SettingService::int('low_stock_threshold', 3);
        return self::available($product) <= $min;
    }
}
