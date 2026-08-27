<?php
/**
 * ARCHIVO: app/models/SearchLog.php
 * ---------------------------------------------------------------------
 * Registro de búsquedas: permite detectar qué repuestos tienen mayor
 * demanda aunque todavía no estén cargados en el catálogo.
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class SearchLog extends Model
{
    protected string $table = 'search_logs';
    protected array $fillable = ['term', 'normalized', 'context', 'results_count', 'ip_hash'];

    public function record(string $term, string $context, int $results, ?string $ipHash = null): void
    {
        $term = trim($term);
        if ($term === '' || mb_strlen($term) < 2 || mb_strlen($term) > 160) {
            return;
        }

        Database::insert('search_logs', [
            'term'          => $term,
            'normalized'    => normalize_search($term),
            'context'       => in_array($context, ['global', 'machine', 'spare_part'], true) ? $context : 'global',
            'results_count' => $results,
            'ip_hash'       => $ipHash,
        ]);
    }

    /** Términos más buscados. @return array<int,array<string,mixed>> */
    public function top(int $days = 30, int $limit = 15, ?string $context = null): array
    {
        $sql = 'SELECT normalized AS term, COUNT(*) AS total,
                       SUM(results_count = 0) AS sin_resultados,
                       MAX(created_at) AS ultima
                  FROM search_logs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)';

        $params = ['days' => $days];

        if ($context !== null) {
            $sql .= ' AND context = :context';
            $params['context'] = $context;
        }

        $sql .= ' GROUP BY normalized ORDER BY total DESC LIMIT ' . max(1, $limit);

        return Database::select($sql, $params);
    }

    /** Búsquedas sin resultados: oportunidades de stock. @return array<int,array<string,mixed>> */
    public function withoutResults(int $days = 30, int $limit = 15): array
    {
        return Database::select(
            'SELECT normalized AS term, COUNT(*) AS total, MAX(created_at) AS ultima
               FROM search_logs
              WHERE results_count = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
              GROUP BY normalized ORDER BY total DESC LIMIT ' . max(1, $limit),
            ['days' => $days]
        );
    }

    public function countSince(int $days = 30): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM search_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)',
            ['d' => $days]
        );
    }
}
