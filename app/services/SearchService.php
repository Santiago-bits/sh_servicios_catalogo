<?php
/**
 * ARCHIVO: app/services/SearchService.php
 * ---------------------------------------------------------------------
 * Buscador inteligente: un mismo término encuentra máquinas, repuestos,
 * códigos internos, códigos OEM, marcas, modelos y compatibilidades.
 *
 * Ejemplo: escribir "8FG25" devuelve la máquina Toyota 8FG25 y todos
 * los repuestos declarados compatibles con ese modelo.
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\SearchLog;
use Core\Database;
use Core\Request;

final class SearchService
{
    /**
     * Búsqueda global.
     *
     * @return array{
     *   term:string, machines:array<int,array<string,mixed>>,
     *   parts:array<int,array<string,mixed>>, compatible:array<int,array<string,mixed>>,
     *   brands:array<int,array<string,mixed>>, categories:array<int,array<string,mixed>>,
     *   total:int, matched_model:?string
     * }
     */
    public static function global(string $term, int $limit = 12, bool $log = true): array
    {
        $term = trim($term);

        $result = [
            'term'          => $term,
            'machines'      => [],
            'parts'         => [],
            'compatible'    => [],
            'brands'        => [],
            'categories'    => [],
            'total'         => 0,
            'matched_model' => null,
        ];

        if (mb_strlen($term) < 2) {
            return $result;
        }

        $product = new Product();

        $result['machines'] = $product->catalog('machine', ['q' => $term], 1, $limit)['data'];
        $result['parts']    = $product->catalog('spare_part', ['q' => $term], 1, $limit)['data'];

        // ¿El término coincide con un modelo de máquina conocido?
        $model = self::matchModel($term);
        if ($model !== null) {
            $result['matched_model'] = $model;
            $compatible = $product->partsForModel($model, $limit);

            // Se quitan los que ya salieron en la búsqueda directa
            $partIds = array_column($result['parts'], 'id');
            $result['compatible'] = array_values(array_filter(
                $compatible,
                static fn (array $p): bool => !in_array($p['id'], $partIds, true)
            ));
        }

        $like = '%' . $term . '%';

        $result['brands'] = Database::select(
            'SELECT id, name, slug, logo FROM brands WHERE active = 1 AND name LIKE :q ORDER BY name ASC LIMIT 5',
            ['q' => $like]
        );

        $result['categories'] = Database::select(
            'SELECT id, name, slug, type, icon FROM categories WHERE active = 1 AND name LIKE :q ORDER BY name ASC LIMIT 5',
            ['q' => $like]
        );

        $result['total'] = count($result['machines']) + count($result['parts']) + count($result['compatible']);

        if ($log && SettingService::bool('track_searches', true)) {
            (new SearchLog())->record($term, 'global', $result['total'], ip_hash(Request::ip()));
        }

        return $result;
    }

    /**
     * Sugerencias para el buscador del navbar (respuesta AJAX liviana).
     *
     * @return array<int,array{type:string,label:string,sub:string,url:string,icon:string,image:?string}>
     */
    public static function suggest(string $term, int $limit = 8): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $like        = '%' . $term . '%';
        $suggestions = [];

        // 1) Coincidencia exacta de código (interno u OEM): lo más útil
        $codes = Database::select(
            'SELECT p.id, p.name, p.code, p.slug, p.type, c.slug AS category_slug, sp.oem_code,
                    (SELECT COALESCE(pi.thumb_path, pi.path) FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_main DESC LIMIT 1) AS thumb
               FROM products p
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
              WHERE p.active = 1 AND p.deleted_at IS NULL
                AND (p.code LIKE :q OR sp.oem_code LIKE :q2 OR sp.manufacturer_code LIKE :q3
                     OR EXISTS (SELECT 1 FROM spare_part_codes spc WHERE spc.product_id = p.id AND spc.code LIKE :q4))
              ORDER BY (p.code = :exact) DESC, p.name ASC
              LIMIT 5',
            ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'exact' => $term]
        );

        foreach ($codes as $row) {
            $suggestions[] = [
                'type'  => $row['type'] === 'machine' ? 'Máquina' : 'Repuesto',
                'label' => (string) $row['name'],
                'sub'   => 'Código ' . $row['code'] . (!empty($row['oem_code']) ? ' · OEM ' . $row['oem_code'] : ''),
                'url'   => product_url($row),
                'icon'  => $row['type'] === 'machine' ? 'bi-truck-front-fill' : 'bi-nut-fill',
                'image' => $row['thumb'] ? upload_url($row['thumb']) : null,
            ];
        }

        // 2) Modelos de máquina compatibles
        $models = Database::select(
            'SELECT DISTINCT spc.model, b.name AS brand_name, COUNT(*) AS total
               FROM spare_part_compatibility spc
               LEFT JOIN brands b ON b.id = spc.brand_id
              WHERE spc.model LIKE :q
              GROUP BY spc.model, b.name
              ORDER BY total DESC LIMIT 3',
            ['q' => $like]
        );

        foreach ($models as $row) {
            $suggestions[] = [
                'type'  => 'Compatibilidad',
                'label' => 'Repuestos para ' . trim(($row['brand_name'] ?? '') . ' ' . $row['model']),
                'sub'   => $row['total'] . ' repuesto(s) compatibles',
                'url'   => url('repuestos?q=' . urlencode((string) $row['model'])),
                'icon'  => 'bi-diagram-3-fill',
                'image' => null,
            ];
        }

        // 3) Nombres de producto
        if (count($suggestions) < $limit) {
            $names = Database::select(
                'SELECT p.id, p.name, p.code, p.slug, p.type, c.slug AS category_slug,
                        (SELECT COALESCE(pi.thumb_path, pi.path) FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_main DESC LIMIT 1) AS thumb
                   FROM products p LEFT JOIN categories c ON c.id = p.category_id
                  WHERE p.active = 1 AND p.deleted_at IS NULL AND p.name LIKE :q
                  ORDER BY p.featured DESC, p.views DESC LIMIT :lim',
                ['q' => $like, 'lim' => $limit - count($suggestions)]
            );

            $existing = array_column($suggestions, 'label');

            foreach ($names as $row) {
                if (in_array($row['name'], $existing, true)) {
                    continue;
                }
                $suggestions[] = [
                    'type'  => $row['type'] === 'machine' ? 'Máquina' : 'Repuesto',
                    'label' => (string) $row['name'],
                    'sub'   => 'Código ' . $row['code'],
                    'url'   => product_url($row),
                    'icon'  => $row['type'] === 'machine' ? 'bi-truck-front-fill' : 'bi-nut-fill',
                    'image' => $row['thumb'] ? upload_url($row['thumb']) : null,
                ];
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * ¿El término escrito coincide con un modelo de máquina?
     * Busca tanto en el catálogo como en las compatibilidades cargadas.
     */
    public static function matchModel(string $term): ?string
    {
        $term = trim($term);
        if (mb_strlen($term) < 3) {
            return null;
        }

        $model = Database::scalar(
            'SELECT model FROM machines WHERE model LIKE :q ORDER BY CHAR_LENGTH(model) ASC LIMIT 1',
            ['q' => '%' . $term . '%']
        );

        if ($model !== null) {
            return (string) $model;
        }

        $model = Database::scalar(
            'SELECT model FROM spare_part_compatibility WHERE model LIKE :q ORDER BY CHAR_LENGTH(model) ASC LIMIT 1',
            ['q' => '%' . $term . '%']
        );

        return $model === null ? null : (string) $model;
    }

    /** Registra una búsqueda hecha dentro de un catálogo. */
    public static function logCatalogSearch(string $term, string $context, int $results): void
    {
        if ($term !== '' && SettingService::bool('track_searches', true)) {
            (new SearchLog())->record($term, $context, $results, ip_hash(Request::ip()));
        }
    }
}
