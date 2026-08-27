<?php
/**
 * ARCHIVO: app/models/Quote.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Quote extends Model
{
    protected string $table = 'quotes';

    protected array $fillable = [
        'number', 'status', 'source',
        'customer_name', 'customer_company', 'customer_email', 'customer_phone',
        'customer_taxid', 'customer_address',
        'subtotal', 'discount_percent', 'discount_amount', 'shipping_cost',
        'other_costs', 'interest_amount', 'total', 'currency', 'exchange_rate',
        'financing_option_id', 'down_payment', 'installments', 'installment_amount',
        'notes', 'conditions', 'valid_until', 'sent_at', 'responded_at', 'user_id',
    ];

    protected array $sortable = ['id', 'number', 'total', 'created_at', 'valid_until', 'status'];

    /**
     * Genera el siguiente número correlativo dentro de una transacción,
     * de modo que dos usuarios simultáneos no obtengan el mismo.
     */
    public function nextNumber(string $prefix = 'COT-', int $padding = 6): string
    {
        $last = (string) Database::scalar(
            'SELECT number FROM quotes WHERE number LIKE :prefix ORDER BY id DESC LIMIT 1',
            ['prefix' => $prefix . '%']
        );

        $next = 1;
        if ($last !== '' && preg_match('/(\d+)$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $next, max(1, $padding), '0', STR_PAD_LEFT);
    }

    /** @return array<string,mixed>|null */
    public function findFull(int $id): ?array
    {
        $quote = Database::selectOne(
            'SELECT q.*, u.name AS user_name, fo.name AS financing_name,
                    fo.interest_percent, fo.down_payment_percent
               FROM quotes q
               LEFT JOIN users u ON u.id = q.user_id
               LEFT JOIN financing_options fo ON fo.id = q.financing_option_id
              WHERE q.id = :id LIMIT 1',
            ['id' => $id]
        );

        if ($quote === null) {
            return null;
        }

        $quote['items']    = $this->items($id);
        $quote['payments'] = $this->payments($id);

        return $quote;
    }

    /** @return array<string,mixed>|null */
    public function findByNumber(string $number): ?array
    {
        return Database::selectOne('SELECT * FROM quotes WHERE number = :n LIMIT 1', ['n' => $number]);
    }

    /** @return array<int,array<string,mixed>> */
    public function items(int $quoteId): array
    {
        return Database::select(
            'SELECT qi.*, p.slug, p.type AS product_type,
                    (SELECT COALESCE(pi.thumb_path, pi.path) FROM product_images pi
                      WHERE pi.product_id = p.id ORDER BY pi.is_main DESC, pi.sort_order ASC LIMIT 1) AS image
               FROM quote_items qi
               LEFT JOIN products p ON p.id = qi.product_id
              WHERE qi.quote_id = :id
              ORDER BY qi.sort_order ASC, qi.id ASC',
            ['id' => $quoteId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function payments(int $quoteId): array
    {
        return Database::select(
            'SELECT * FROM quote_payments WHERE quote_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $quoteId]
        );
    }

    /** @param array<int,array<string,mixed>> $items */
    public function replaceItems(int $quoteId, array $items): void
    {
        Database::delete('quote_items', 'quote_id = :id', ['id' => $quoteId]);

        foreach (array_values($items) as $index => $item) {
            Database::insert('quote_items', [
                'quote_id'         => $quoteId,
                'product_id'       => $item['product_id'] ?: null,
                'item_type'        => $item['item_type'] ?? 'other',
                'code'             => $item['code'] ?? null,
                'description'      => mb_substr((string) $item['description'], 0, 255),
                'quantity'         => (float) $item['quantity'],
                'unit_price'       => (float) $item['unit_price'],
                'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                'line_total'       => (float) $item['line_total'],
                'sort_order'       => $index,
            ]);
        }
    }

    /** @param array<int,array<string,mixed>> $payments */
    public function replacePayments(int $quoteId, array $payments): void
    {
        Database::delete('quote_payments', 'quote_id = :id', ['id' => $quoteId]);

        foreach (array_values($payments) as $index => $payment) {
            Database::insert('quote_payments', [
                'quote_id'   => $quoteId,
                'concept'    => mb_substr((string) $payment['concept'], 0, 160),
                'amount'     => (float) $payment['amount'],
                'due_date'   => $payment['due_date'] ?: null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Listado paginado con filtros del panel.
     *
     * @param array<string,mixed> $filters
     */
    public function search(array $filters, int $page = 1, int $perPage = 20): array
    {
        $conditions = ['1 = 1'];
        $params     = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(q.number LIKE :q OR q.customer_name LIKE :q2 OR q.customer_company LIKE :q3 OR q.customer_email LIKE :q4)';
            $like = '%' . $filters['q'] . '%';
            $params += ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        if (!empty($filters['estado']) && in_array($filters['estado'], ['borrador', 'enviada', 'aceptada', 'rechazada', 'vencida'], true)) {
            $conditions[]     = 'q.status = :status';
            $params['status'] = $filters['estado'];
        }

        if (!empty($filters['desde'])) {
            $conditions[]    = 'q.created_at >= :desde';
            $params['desde'] = $filters['desde'] . ' 00:00:00';
        }
        if (!empty($filters['hasta'])) {
            $conditions[]    = 'q.created_at <= :hasta';
            $params['hasta'] = $filters['hasta'] . ' 23:59:59';
        }
        if (!empty($filters['usuario'])) {
            $conditions[]      = 'q.user_id = :user';
            $params['user']    = (int) $filters['usuario'];
        }

        $where = implode(' AND ', $conditions);

        $sql = 'SELECT q.*, u.name AS user_name,
                       (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id = q.id) AS items_count
                  FROM quotes q LEFT JOIN users u ON u.id = q.user_id
                 WHERE ' . $where . ' ORDER BY q.created_at DESC';

        return self::paginateRaw($sql, 'SELECT COUNT(*) FROM quotes q WHERE ' . $where, $params, $page, $perPage);
    }

    /** Marca como vencidas las cotizaciones cuya validez pasó. */
    public function expireOverdue(): int
    {
        return Database::execute(
            'UPDATE quotes SET status = \'vencida\'
              WHERE status = \'enviada\' AND valid_until IS NOT NULL AND valid_until < CURDATE()'
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function expiringSoon(int $days = 5): array
    {
        return Database::select(
            'SELECT id, number, customer_name, valid_until, total, currency
               FROM quotes
              WHERE status = \'enviada\' AND valid_until IS NOT NULL
                AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :d DAY)
              ORDER BY valid_until ASC',
            ['d' => $days]
        );
    }

    /** @return array<string,mixed> */
    public function stats(): array
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total,
                    SUM(status = \'borrador\')  AS borradores,
                    SUM(status = \'enviada\')   AS enviadas,
                    SUM(status = \'aceptada\')  AS aceptadas,
                    SUM(status = \'rechazada\') AS rechazadas,
                    SUM(CASE WHEN status = \'aceptada\' THEN total ELSE 0 END) AS monto_aceptado
               FROM quotes'
        );

        return $row ?? [];
    }
}
