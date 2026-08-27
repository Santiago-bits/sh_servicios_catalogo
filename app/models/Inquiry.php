<?php
/**
 * ARCHIVO: app/models/Inquiry.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Inquiry extends Model
{
    protected string $table = 'inquiries';

    protected array $fillable = [
        'name', 'email', 'phone', 'company', 'product_id', 'subject', 'message',
        'channel', 'status', 'assigned_to', 'internal_note', 'ip', 'user_agent', 'replied_at',
    ];

    protected array $sortable = ['id', 'name', 'status', 'created_at'];

    /** @param array<string,mixed> $filters */
    public function search(array $filters, int $page = 1, int $perPage = 20): array
    {
        $conditions = ['1 = 1'];
        $params     = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(i.name LIKE :q OR i.email LIKE :q2 OR i.company LIKE :q3 OR i.message LIKE :q4)';
            $like = '%' . $filters['q'] . '%';
            $params += ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        if (!empty($filters['estado']) && in_array($filters['estado'], ['nueva', 'en_proceso', 'respondida', 'cerrada'], true)) {
            $conditions[]     = 'i.status = :status';
            $params['status'] = $filters['estado'];
        }

        if (!empty($filters['canal']) && in_array($filters['canal'], ['web', 'whatsapp', 'email', 'telefono'], true)) {
            $conditions[]      = 'i.channel = :canal';
            $params['canal']   = $filters['canal'];
        }

        $where = implode(' AND ', $conditions);

        $sql = 'SELECT i.*, p.name AS product_name, p.code AS product_code, p.type AS product_type, p.slug AS product_slug,
                       u.name AS assigned_name
                  FROM inquiries i
                  LEFT JOIN products p ON p.id = i.product_id
                  LEFT JOIN users u    ON u.id = i.assigned_to
                 WHERE ' . $where . ' ORDER BY i.created_at DESC';

        return self::paginateRaw($sql, 'SELECT COUNT(*) FROM inquiries i WHERE ' . $where, $params, $page, $perPage);
    }

    /** @return array<string,mixed>|null */
    public function findFull(int $id): ?array
    {
        return Database::selectOne(
            'SELECT i.*, p.name AS product_name, p.code AS product_code, p.slug AS product_slug, p.type AS product_type,
                    u.name AS assigned_name
               FROM inquiries i
               LEFT JOIN products p ON p.id = i.product_id
               LEFT JOIN users u    ON u.id = i.assigned_to
              WHERE i.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function countNew(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM inquiries WHERE status = \'nueva\'');
    }

    /** @return array<int,array<string,mixed>> */
    public function latest(int $limit = 5): array
    {
        return Database::select(
            'SELECT i.id, i.name, i.company, i.subject, i.status, i.created_at, p.name AS product_name
               FROM inquiries i LEFT JOIN products p ON p.id = i.product_id
              ORDER BY i.created_at DESC LIMIT ' . max(1, $limit)
        );
    }

    /** Anti spam simple: máximo N consultas por IP por hora. */
    public function tooManyFrom(string $ip, int $max = 5): bool
    {
        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM inquiries WHERE ip = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            ['ip' => $ip]
        );
        return $count >= $max;
    }
}
