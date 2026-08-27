<?php
/**
 * ARCHIVO: app/models/FinancingOption.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class FinancingOption extends Model
{
    protected string $table = 'financing_options';

    protected array $fillable = [
        'payment_method_id', 'name', 'description', 'down_payment_percent',
        'installments', 'interest_percent', 'interest_type', 'min_amount',
        'max_amount', 'currency', 'applies_to', 'featured', 'sort_order', 'active',
    ];

    protected array $sortable = ['id', 'name', 'installments', 'sort_order'];

    /** @return array<int,array<string,mixed>> */
    public function activeFor(string $type, ?float $amount = null): array
    {
        $sql = 'SELECT fo.*, pm.name AS method_name, pm.slug AS method_slug, pm.icon AS method_icon,
                       pm.discount_percent AS method_discount
                  FROM financing_options fo
                  LEFT JOIN payment_methods pm ON pm.id = fo.payment_method_id
                 WHERE fo.active = 1 AND (fo.applies_to = :type OR fo.applies_to = \'both\')';

        $params = ['type' => $type];

        if ($amount !== null && $amount > 0) {
            $sql .= ' AND (fo.min_amount IS NULL OR fo.min_amount <= :amount)
                      AND (fo.max_amount IS NULL OR fo.max_amount >= :amount2)';
            $params['amount']  = $amount;
            $params['amount2'] = $amount;
        }

        $sql .= ' ORDER BY fo.featured DESC, fo.sort_order ASC, fo.installments ASC';

        return Database::select($sql, $params);
    }

    /** @return array<int,array<string,mixed>> */
    public function allWithMethod(): array
    {
        return Database::select(
            'SELECT fo.*, pm.name AS method_name
               FROM financing_options fo
               LEFT JOIN payment_methods pm ON pm.id = fo.payment_method_id
              ORDER BY fo.sort_order ASC, fo.id ASC'
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function paymentMethods(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM payment_methods';
        if ($onlyActive) {
            $sql .= ' WHERE active = 1';
        }
        return Database::select($sql . ' ORDER BY sort_order ASC, name ASC');
    }
}
