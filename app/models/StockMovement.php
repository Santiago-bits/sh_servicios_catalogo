<?php
/**
 * ARCHIVO: app/models/StockMovement.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class StockMovement extends Model
{
    protected string $table = 'stock_movements';

    protected array $fillable = [
        'product_id', 'type', 'quantity', 'stock_before', 'stock_after',
        'reason', 'reference', 'user_id',
    ];

    protected array $sortable = ['id', 'created_at', 'type'];

    /** @return array<int,array<string,mixed>> */
    public function forProduct(int $productId, int $limit = 50): array
    {
        return Database::select(
            'SELECT sm.*, u.name AS user_name
               FROM stock_movements sm LEFT JOIN users u ON u.id = sm.user_id
              WHERE sm.product_id = :id
              ORDER BY sm.created_at DESC, sm.id DESC
              LIMIT ' . max(1, $limit),
            ['id' => $productId]
        );
    }

    /** @param array<string,mixed> $filters */
    public function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        $conditions = ['1 = 1'];
        $params     = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(p.name LIKE :q OR p.code LIKE :q2 OR sm.reference LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params += ['q' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($filters['tipo']) && in_array($filters['tipo'], ['entrada', 'salida', 'reserva', 'liberacion', 'ajuste', 'venta'], true)) {
            $conditions[]    = 'sm.type = :tipo';
            $params['tipo']  = $filters['tipo'];
        }
        if (!empty($filters['producto'])) {
            $conditions[]        = 'sm.product_id = :producto';
            $params['producto']  = (int) $filters['producto'];
        }

        $where = implode(' AND ', $conditions);

        $sql = 'SELECT sm.*, p.name AS product_name, p.code AS product_code, u.name AS user_name
                  FROM stock_movements sm
                  INNER JOIN products p ON p.id = sm.product_id
                  LEFT JOIN users u ON u.id = sm.user_id
                 WHERE ' . $where . ' ORDER BY sm.created_at DESC, sm.id DESC';

        $countSql = 'SELECT COUNT(*) FROM stock_movements sm INNER JOIN products p ON p.id = sm.product_id WHERE ' . $where;

        return self::paginateRaw($sql, $countSql, $params, $page, $perPage);
    }

    /** @return array<int,array<string,mixed>> Productos con stock por debajo del mínimo. */
    public function lowStock(int $limit = 20): array
    {
        return Database::select(
            'SELECT p.id, p.code, p.name, p.stock, p.stock_reserved, p.stock_min, p.slug, p.type,
                    (p.stock - p.stock_reserved) AS available
               FROM products p
              WHERE p.type = \'spare_part\' AND p.active = 1 AND p.deleted_at IS NULL AND p.track_stock = 1
                AND (p.stock - p.stock_reserved) <= GREATEST(p.stock_min, 0)
              ORDER BY available ASC, p.name ASC
              LIMIT ' . max(1, $limit)
        );
    }

    public function countLowStock(): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM products
              WHERE type = \'spare_part\' AND active = 1 AND deleted_at IS NULL AND track_stock = 1
                AND (stock - stock_reserved) <= GREATEST(stock_min, 0)'
        );
    }
}
