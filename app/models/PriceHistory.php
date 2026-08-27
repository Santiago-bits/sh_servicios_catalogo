<?php
/**
 * ARCHIVO: app/models/PriceHistory.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class PriceHistory extends Model
{
    protected string $table = 'price_history';

    protected array $fillable = [
        'product_id', 'old_cost', 'new_cost', 'old_profit_percent', 'new_profit_percent',
        'old_profit_amount', 'new_profit_amount', 'old_price', 'new_price',
        'currency', 'reason', 'user_id',
    ];

    protected array $sortable = ['id', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public function forProduct(int $productId, int $limit = 50): array
    {
        return Database::select(
            'SELECT ph.*, u.name AS user_name
               FROM price_history ph LEFT JOIN users u ON u.id = ph.user_id
              WHERE ph.product_id = :id
              ORDER BY ph.created_at DESC, ph.id DESC
              LIMIT ' . max(1, $limit),
            ['id' => $productId]
        );
    }

    /** @return array<int,array<string,mixed>> Últimos cambios de precio del sistema. */
    public function latest(int $limit = 15): array
    {
        return Database::select(
            'SELECT ph.*, p.name AS product_name, p.code AS product_code, u.name AS user_name
               FROM price_history ph
               INNER JOIN products p ON p.id = ph.product_id
               LEFT JOIN users u ON u.id = ph.user_id
              ORDER BY ph.created_at DESC, ph.id DESC
              LIMIT ' . max(1, $limit)
        );
    }
}
