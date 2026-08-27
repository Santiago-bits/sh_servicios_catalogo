<?php
/**
 * ARCHIVO: app/models/ActivityLog.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class ActivityLog extends Model
{
    protected string $table = 'activity_logs';
    protected array $sortable = ['id', 'created_at', 'action', 'module'];

    /** @param array<string,mixed> $filters */
    public function search(array $filters, int $page = 1, int $perPage = 40): array
    {
        $conditions = ['1 = 1'];
        $params     = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(al.description LIKE :q OR al.user_name LIKE :q2 OR al.ip LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params += ['q' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($filters['modulo'])) {
            $conditions[]      = 'al.module = :modulo';
            $params['modulo']  = $filters['modulo'];
        }
        if (!empty($filters['accion'])) {
            $conditions[]      = 'al.action = :accion';
            $params['accion']  = $filters['accion'];
        }
        if (!empty($filters['usuario'])) {
            $conditions[]      = 'al.user_id = :usuario';
            $params['usuario'] = (int) $filters['usuario'];
        }
        if (!empty($filters['desde'])) {
            $conditions[]    = 'al.created_at >= :desde';
            $params['desde'] = $filters['desde'] . ' 00:00:00';
        }
        if (!empty($filters['hasta'])) {
            $conditions[]    = 'al.created_at <= :hasta';
            $params['hasta'] = $filters['hasta'] . ' 23:59:59';
        }

        $where = implode(' AND ', $conditions);

        $sql = 'SELECT al.* FROM activity_logs al WHERE ' . $where . ' ORDER BY al.created_at DESC, al.id DESC';

        return self::paginateRaw($sql, 'SELECT COUNT(*) FROM activity_logs al WHERE ' . $where, $params, $page, $perPage);
    }

    /** @return array<int,string> */
    public function distinctModules(): array
    {
        return array_column(Database::select('SELECT DISTINCT module FROM activity_logs ORDER BY module ASC'), 'module');
    }

    /** @return array<int,string> */
    public function distinctActions(): array
    {
        return array_column(Database::select('SELECT DISTINCT action FROM activity_logs ORDER BY action ASC'), 'action');
    }

    /** @return array<int,array<string,mixed>> */
    public function latest(int $limit = 8): array
    {
        return Database::select(
            'SELECT * FROM activity_logs ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit)
        );
    }
}
