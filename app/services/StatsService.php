<?php
/**
 * ARCHIVO: app/services/StatsService.php
 * ---------------------------------------------------------------------
 * Métricas del dashboard y del módulo de estadísticas.
 */

declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Request;

final class StatsService
{
    /** Registra una visita a la ficha de un producto (una por sesión). */
    public static function trackView(int $productId): void
    {
        if (!SettingService::bool('track_views', true)) {
            return;
        }

        $seen = $_SESSION['_viewed_products'] ?? [];
        if (in_array($productId, $seen, true)) {
            return;
        }

        $seen[] = $productId;
        $_SESSION['_viewed_products'] = array_slice($seen, -80);

        try {
            Database::insert('product_views', [
                'product_id' => $productId,
                'ip_hash'    => ip_hash(Request::ip()),
                'referer'    => Request::referer() ?: null,
                'device'     => Request::device(),
            ]);
            Database::execute('UPDATE products SET views = views + 1 WHERE id = :id', ['id' => $productId]);
        } catch (\Throwable $e) {
            error_log('[STATS] ' . $e->getMessage());
        }
    }

    /** @return array<string,int|float> */
    public static function dashboard(): array
    {
        $row = Database::selectOne(
            "SELECT
                (SELECT COUNT(*) FROM products WHERE deleted_at IS NULL)                             AS productos,
                (SELECT COUNT(*) FROM products WHERE type = 'machine' AND deleted_at IS NULL)        AS maquinas,
                (SELECT COUNT(*) FROM products WHERE type = 'spare_part' AND deleted_at IS NULL)     AS repuestos,
                (SELECT COUNT(*) FROM products WHERE active = 1 AND deleted_at IS NULL)              AS activos,
                (SELECT COUNT(*) FROM inquiries)                                                     AS consultas,
                (SELECT COUNT(*) FROM inquiries WHERE status = 'nueva')                              AS consultas_nuevas,
                (SELECT COUNT(*) FROM quotes)                                                        AS cotizaciones,
                (SELECT COUNT(*) FROM quotes WHERE status = 'enviada')                               AS cotizaciones_enviadas,
                (SELECT COALESCE(SUM(total),0) FROM quotes WHERE status = 'aceptada')                AS monto_aceptado,
                (SELECT COUNT(*) FROM products
                  WHERE type = 'spare_part' AND track_stock = 1 AND active = 1 AND deleted_at IS NULL
                    AND (stock - stock_reserved) <= GREATEST(stock_min,0))                           AS stock_bajo,
                (SELECT COALESCE(SUM(views),0) FROM products)                                        AS visitas_totales,
                (SELECT COUNT(*) FROM product_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS visitas_mes,
                (SELECT COUNT(*) FROM search_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))   AS busquedas_mes,
                (SELECT COUNT(*) FROM brands WHERE active = 1)                                       AS marcas,
                (SELECT COUNT(*) FROM categories WHERE active = 1)                                   AS categorias"
        );

        return array_map(static fn ($v) => is_numeric($v) ? $v + 0 : 0, $row ?? []);
    }

    /** Visitas por día de los últimos N días. @return array{labels:array<int,string>,values:array<int,int>} */
    public static function viewsByDay(int $days = 30): array
    {
        $rows = Database::select(
            'SELECT DATE(created_at) AS dia, COUNT(*) AS total
               FROM product_views
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
              GROUP BY DATE(created_at) ORDER BY dia ASC',
            ['days' => $days]
        );

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['dia']] = (int) $row['total'];
        }

        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime('-' . $i . ' days'));
            $labels[] = date('d/m', strtotime($date));
            $values[] = $byDate[$date] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array<int,array<string,mixed>> */
    public static function topProducts(string $type = 'machine', int $limit = 10): array
    {
        return Database::select(
            'SELECT p.id, p.name, p.code, p.slug, p.type, p.views, p.inquiries_count, p.quotes_count,
                    c.slug AS category_slug,
                    (SELECT COUNT(*) FROM favorites f WHERE f.product_id = p.id) AS favoritos
               FROM products p LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.type = :type AND p.deleted_at IS NULL
              ORDER BY p.views DESC LIMIT ' . max(1, $limit),
            ['type' => $type]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function topCategories(int $limit = 8): array
    {
        return Database::select(
            'SELECT c.name, c.type, COUNT(pv.id) AS visitas, COUNT(DISTINCT p.id) AS productos
               FROM categories c
               LEFT JOIN products p      ON p.category_id = c.id AND p.deleted_at IS NULL
               LEFT JOIN product_views pv ON pv.product_id = p.id
              GROUP BY c.id, c.name, c.type
              HAVING productos > 0
              ORDER BY visitas DESC, productos DESC
              LIMIT ' . max(1, $limit)
        );
    }

    /** @return array{labels:array<int,string>,values:array<int,int>} */
    public static function quotesByMonth(int $months = 12): array
    {
        $rows = Database::select(
            'SELECT DATE_FORMAT(created_at, \'%Y-%m\') AS mes, COUNT(*) AS total, COALESCE(SUM(total),0) AS monto
               FROM quotes
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
              GROUP BY mes ORDER BY mes ASC',
            ['m' => $months]
        );

        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[$row['mes']] = (int) $row['total'];
        }

        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $key      = date('Y-m', strtotime('-' . $i . ' months'));
            $labels[] = date('m/y', strtotime($key . '-01'));
            $values[] = $byMonth[$key] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array{labels:array<int,string>,values:array<int,int>} */
    public static function inquiriesByChannel(): array
    {
        $rows = Database::select(
            'SELECT channel, COUNT(*) AS total FROM inquiries GROUP BY channel ORDER BY total DESC'
        );

        $map = ['web' => 'Formulario web', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'telefono' => 'Teléfono'];

        return [
            'labels' => array_map(static fn (array $r): string => $map[$r['channel']] ?? $r['channel'], $rows),
            'values' => array_map(static fn (array $r): int => (int) $r['total'], $rows),
        ];
    }

    /** Resumen por producto para el detalle de estadísticas. @return array<string,mixed> */
    public static function productDetail(int $productId): array
    {
        $row = Database::selectOne(
            'SELECT p.name, p.code, p.views, p.inquiries_count, p.quotes_count,
                    (SELECT COUNT(*) FROM favorites f WHERE f.product_id = p.id) AS favoritos,
                    (SELECT COUNT(*) FROM product_views pv WHERE pv.product_id = p.id
                      AND pv.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS visitas_mes
               FROM products p WHERE p.id = :id',
            ['id' => $productId]
        );

        return $row ?? [];
    }

    /** Distribución de máquinas por categoría. @return array{labels:array<int,string>,values:array<int,int>} */
    public static function productsByCategory(string $type = 'machine'): array
    {
        $rows = Database::select(
            'SELECT c.name, COUNT(p.id) AS total
               FROM categories c
               INNER JOIN products p ON p.category_id = c.id AND p.deleted_at IS NULL AND p.type = :type
              GROUP BY c.id, c.name ORDER BY total DESC LIMIT 10',
            ['type' => $type]
        );

        return [
            'labels' => array_column($rows, 'name'),
            'values' => array_map('intval', array_column($rows, 'total')),
        ];
    }
}
