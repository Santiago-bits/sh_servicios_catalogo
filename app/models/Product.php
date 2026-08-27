<?php
/**
 * ARCHIVO: app/models/Product.php
 * ---------------------------------------------------------------------
 * Modelo central del catálogo. Sirve tanto a maquinaria como a
 * repuestos (cada uno con su tabla de extensión).
 *
 * REGLA DE SEGURIDAD: los métodos "public*" NUNCA seleccionan
 * cost_price, profit_percent ni profit_amount. El backend decide qué
 * columnas viajan al navegador; no se oculta con CSS.
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = [
        'type', 'code', 'name', 'slug', 'brand_id', 'category_id',
        'short_description', 'description',
        'cost_price', 'profit_percent', 'profit_amount', 'final_price',
        'offer_price', 'currency', 'price_visible', 'price_updated_at',
        'availability', 'featured', 'is_new', 'is_offer',
        'stock', 'stock_reserved', 'stock_min', 'track_stock',
        'meta_title', 'meta_description', 'og_image',
        'views', 'active', 'created_by', 'updated_by',
    ];

    protected array $sortable = ['id', 'name', 'code', 'final_price', 'views', 'created_at', 'updated_at', 'stock'];

    /** Columnas que pueden viajar al sitio público. */
    public const PUBLIC_COLUMNS = 'p.id, p.type, p.code, p.name, p.slug, p.short_description,
        p.final_price, p.offer_price, p.currency, p.price_visible, p.availability,
        p.featured, p.is_new, p.is_offer, p.stock, p.stock_reserved, p.stock_min,
        p.track_stock, p.views, p.meta_title, p.meta_description, p.og_image,
        p.category_id, p.brand_id, p.created_at';

    /** Columnas internas (incluye costo y ganancia). Sólo con permiso. */
    public const INTERNAL_COLUMNS = self::PUBLIC_COLUMNS . ', p.cost_price, p.profit_percent, p.profit_amount, p.description, p.active, p.price_updated_at';

    private const JOIN_BASE = '
        FROM products p
        LEFT JOIN brands b     ON b.id = p.brand_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN machines m   ON m.product_id = p.id
        LEFT JOIN spare_parts sp ON sp.product_id = p.id';

    private const EXTRA_COLUMNS = "
        b.name AS brand_name, b.slug AS brand_slug, b.logo AS brand_logo,
        c.name AS category_name, c.slug AS category_slug,
        m.model, m.year, m.hours, m.fuel, m.condition_type, m.capacity_kg,
        m.lift_height_mm, m.power_hp, m.location, m.voltage, m.engine, m.transmission,
        m.weight_kg, m.length_mm, m.width_mm, m.turn_radius_mm, m.closed_height_mm,
        m.battery, m.mast_type, m.tire_type, m.serial_number, m.warranty,
        sp.oem_code, sp.manufacturer_code, sp.manufacturer, sp.origin, sp.unit,
        sp.warehouse_id, sp.sector, sp.shelf, sp.position, sp.lead_time_days,
        (SELECT pi.path  FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_main DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS image,
        (SELECT COALESCE(pi.thumb_path, pi.path) FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_main DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) AS thumb";

    /** Ordenamientos permitidos (lista blanca: nada llega crudo al SQL). */
    private const ORDERS = [
        'destacados'  => 'p.featured DESC, p.updated_at DESC',
        'precio_asc'  => 'p.final_price ASC, p.name ASC',
        'precio_desc' => 'p.final_price DESC, p.name ASC',
        'nuevos'      => 'p.created_at DESC',
        'vistos'      => 'p.views DESC',
        'az'          => 'p.name ASC',
        'za'          => 'p.name DESC',
        'stock'       => 'p.stock DESC',
        'codigo'      => 'p.code ASC',
    ];

    // =================================================================
    // Catálogo público
    // =================================================================

    /**
     * Listado paginado del catálogo con filtros.
     *
     * @param array<string,mixed> $filters
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,per_page:int,last_page:int,from:int,to:int}
     */
    public function catalog(string $type, array $filters = [], int $page = 1, int $perPage = 12, bool $internal = false): array
    {
        [$where, $params] = $this->buildCatalogFilters($type, $filters, $internal);

        $columns = $internal ? self::INTERNAL_COLUMNS : self::PUBLIC_COLUMNS;
        $order   = self::ORDERS[$filters['orden'] ?? ''] ?? self::ORDERS['destacados'];

        $sql = 'SELECT ' . $columns . ', ' . self::EXTRA_COLUMNS . self::JOIN_BASE
             . ' WHERE ' . $where
             . ' ORDER BY ' . $order;

        $countSql = 'SELECT COUNT(DISTINCT p.id)' . self::JOIN_BASE . ' WHERE ' . $where;

        $result = self::paginateRaw($sql, $countSql, $params, $page, $perPage);
        $result['data'] = $this->attachTags($result['data']);

        return $result;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildCatalogFilters(string $type, array $filters, bool $internal): array
    {
        $conditions = ['p.type = :type', 'p.deleted_at IS NULL'];
        $params     = ['type' => $type === 'spare_part' ? 'spare_part' : 'machine'];

        if (!$internal) {
            $conditions[] = 'p.active = 1';
        } elseif (isset($filters['activo']) && $filters['activo'] !== '') {
            $conditions[]     = 'p.active = :active';
            $params['active'] = (int) $filters['activo'];
        }

        // --- Categoría (acepta slug o id, incluye subcategorías) -----
        if (!empty($filters['categoria'])) {
            if (ctype_digit((string) $filters['categoria'])) {
                $conditions[]  = '(p.category_id = :cat OR c.parent_id = :cat)';
                $params['cat'] = (int) $filters['categoria'];
            } else {
                $conditions[]      = '(c.slug = :catslug OR c.parent_id = (SELECT id FROM categories c2 WHERE c2.slug = :catslug2 AND c2.type = :type2 LIMIT 1))';
                $params['catslug']  = (string) $filters['categoria'];
                $params['catslug2'] = (string) $filters['categoria'];
                $params['type2']    = $params['type'];
            }
        }

        // --- Marca ---------------------------------------------------
        if (!empty($filters['marca'])) {
            $marcas = is_array($filters['marca']) ? $filters['marca'] : [$filters['marca']];
            $in     = [];
            foreach (array_values($marcas) as $i => $marca) {
                if ($marca === '' || $marca === null) {
                    continue;
                }
                $key         = 'brand' . $i;
                $in[]        = ':' . $key;
                $params[$key] = ctype_digit((string) $marca) ? (int) $marca : (string) $marca;
            }
            if ($in !== []) {
                $conditions[] = ctype_digit((string) $marcas[array_key_first($marcas)])
                    ? 'p.brand_id IN (' . implode(',', $in) . ')'
                    : 'b.slug IN (' . implode(',', $in) . ')';
            }
        }

        // --- Texto libre ---------------------------------------------
        if (!empty($filters['q'])) {
            $conditions[] = '(p.name LIKE :q OR p.code LIKE :q2 OR p.short_description LIKE :q3
                              OR m.model LIKE :q4 OR sp.oem_code LIKE :q5 OR sp.manufacturer_code LIKE :q6
                              OR b.name LIKE :q7
                              OR EXISTS (SELECT 1 FROM spare_part_codes spc WHERE spc.product_id = p.id AND spc.code LIKE :q8)
                              OR EXISTS (SELECT 1 FROM spare_part_compatibility spcm WHERE spcm.spare_part_id = p.id AND spcm.model LIKE :q9))';
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $filters['q']) . '%';
            for ($i = 1; $i <= 9; $i++) {
                $params['q' . ($i === 1 ? '' : $i)] = $like;
            }
        }

        // --- Modelo --------------------------------------------------
        if (!empty($filters['modelo'])) {
            $conditions[]     = 'm.model LIKE :modelo';
            $params['modelo'] = '%' . (string) $filters['modelo'] . '%';
        }

        // --- Rangos numéricos ---------------------------------------
        $ranges = [
            'precio_min'   => ['p.final_price >= :precio_min', 'float'],
            'precio_max'   => ['p.final_price <= :precio_max', 'float'],
            'anio_min'     => ['m.year >= :anio_min', 'int'],
            'anio_max'     => ['m.year <= :anio_max', 'int'],
            'capacidad_min'=> ['m.capacity_kg >= :capacidad_min', 'float'],
            'capacidad_max'=> ['m.capacity_kg <= :capacidad_max', 'float'],
            'altura_min'   => ['m.lift_height_mm >= :altura_min', 'int'],
            'altura_max'   => ['m.lift_height_mm <= :altura_max', 'int'],
        ];

        foreach ($ranges as $key => [$clause, $cast]) {
            if (isset($filters[$key]) && $filters[$key] !== '' && $filters[$key] !== null) {
                $conditions[]  = $clause;
                $params[$key]  = $cast === 'int' ? (int) $filters[$key] : (float) $filters[$key];
            }
        }

        // --- Enumerados ----------------------------------------------
        if (!empty($filters['combustible'])) {
            $valid = ['electrico', 'diesel', 'nafta', 'gas', 'glp', 'hibrido', 'manual'];
            $fuels = array_values(array_intersect(
                is_array($filters['combustible']) ? $filters['combustible'] : [$filters['combustible']],
                $valid
            ));
            if ($fuels !== []) {
                $in = [];
                foreach ($fuels as $i => $fuel) {
                    $in[] = ':fuel' . $i;
                    $params['fuel' . $i] = $fuel;
                }
                $conditions[] = 'm.fuel IN (' . implode(',', $in) . ')';
            }
        }

        if (!empty($filters['estado'])) {
            $valid = ['disponible', 'reservada', 'vendida', 'mantenimiento', 'consultar'];
            if (in_array((string) $filters['estado'], $valid, true)) {
                $conditions[]      = 'p.availability = :estado';
                $params['estado']  = (string) $filters['estado'];
            }
        }

        if (!empty($filters['condicion'])) {
            $valid = ['nuevo', 'usado', 'reacondicionado'];
            if (in_array((string) $filters['condicion'], $valid, true)) {
                $conditions[]        = 'm.condition_type = :condicion';
                $params['condicion'] = (string) $filters['condicion'];
            }
        }

        if (!empty($filters['ubicacion'])) {
            $conditions[]        = 'm.location LIKE :ubicacion';
            $params['ubicacion'] = '%' . (string) $filters['ubicacion'] . '%';
        }

        // --- Etiqueta ------------------------------------------------
        if (!empty($filters['etiqueta'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_tags pt INNER JOIN tags t ON t.id = pt.tag_id
                                      WHERE pt.product_id = p.id AND t.slug = :etiqueta)';
            $params['etiqueta'] = (string) $filters['etiqueta'];
        }

        // --- Flags ----------------------------------------------------
        if (!empty($filters['destacados'])) {
            $conditions[] = 'p.featured = 1';
        }
        if (!empty($filters['ofertas'])) {
            $conditions[] = 'p.is_offer = 1';
        }
        if (!empty($filters['con_stock'])) {
            $conditions[] = '(p.track_stock = 0 OR (p.stock - p.stock_reserved) > 0)';
        }
        if (!empty($filters['stock_bajo'])) {
            $conditions[] = 'p.track_stock = 1 AND (p.stock - p.stock_reserved) <= GREATEST(p.stock_min, 0)';
        }
        if (!empty($filters['sin_precio'])) {
            $conditions[] = 'p.final_price <= 0';
        }
        if (!empty($filters['sin_imagen'])) {
            $conditions[] = 'NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)';
        }
        if (!empty($filters['compatible_con'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM machine_spare_parts msp WHERE msp.spare_part_id = p.id AND msp.machine_id = :compat)';
            $params['compat'] = (int) $filters['compatible_con'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    // =================================================================
    // Ficha de producto
    // =================================================================

    /** @return array<string,mixed>|null */
    public function findBySlug(string $slug, string $type, bool $internal = false): ?array
    {
        $columns    = $internal ? self::INTERNAL_COLUMNS : self::PUBLIC_COLUMNS;
        $activeOnly = $internal ? '' : ' AND p.active = 1';

        $product = Database::selectOne(
            'SELECT ' . $columns . ', p.description, ' . self::EXTRA_COLUMNS . self::JOIN_BASE .
            ' WHERE p.slug = :slug AND p.type = :type AND p.deleted_at IS NULL' . $activeOnly . ' LIMIT 1',
            ['slug' => $slug, 'type' => $type]
        );

        return $product;
    }

    /** @return array<string,mixed>|null Producto completo para el panel. */
    public function findFull(int $id): ?array
    {
        return Database::selectOne(
            'SELECT p.*, ' . self::EXTRA_COLUMNS . self::JOIN_BASE . ' WHERE p.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function images(int $productId): array
    {
        return Database::select(
            'SELECT * FROM product_images WHERE product_id = :id ORDER BY is_main DESC, sort_order ASC, id ASC',
            ['id' => $productId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function documents(int $productId, bool $publicOnly = true): array
    {
        $sql = 'SELECT * FROM documents WHERE product_id = :id';
        if ($publicOnly) {
            $sql .= ' AND public = 1';
        }
        return Database::select($sql . ' ORDER BY doc_type ASC, id ASC', ['id' => $productId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function videos(int $productId): array
    {
        return Database::select(
            'SELECT * FROM videos WHERE product_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $productId]
        );
    }

    /** Características técnicas agrupadas. @return array<string,array<int,array<string,mixed>>> */
    public function features(int $productId, bool $publicOnly = true): array
    {
        $sql = 'SELECT f.name, f.slug, f.unit, f.group_name, f.sort_order, fv.value_text, fv.value_number
                  FROM feature_values fv
                  INNER JOIN features f ON f.id = fv.feature_id
                 WHERE fv.product_id = :id';
        if ($publicOnly) {
            $sql .= ' AND f.public = 1 AND f.active = 1';
        }
        $sql .= ' ORDER BY f.sort_order ASC, f.name ASC';

        $rows    = Database::select($sql, ['id' => $productId]);
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row['group_name'] ?: 'General'][] = $row;
        }

        return $grouped;
    }

    /** @return array<int,array<string,mixed>> */
    public function tags(int $productId): array
    {
        return Database::select(
            'SELECT t.* FROM product_tags pt INNER JOIN tags t ON t.id = pt.tag_id
              WHERE pt.product_id = :id AND t.active = 1 ORDER BY t.sort_order ASC',
            ['id' => $productId]
        );
    }

    /**
     * Agrega las etiquetas a un listado sin hacer N+1 consultas.
     *
     * @param array<int,array<string,mixed>> $products
     * @return array<int,array<string,mixed>>
     */
    public function attachTags(array $products): array
    {
        if ($products === []) {
            return $products;
        }

        $ids = array_column($products, 'id');
        $in  = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $in[] = ':id' . $i;
            $params['id' . $i] = (int) $id;
        }

        $rows = Database::select(
            'SELECT pt.product_id, t.name, t.slug, t.color, t.icon
               FROM product_tags pt INNER JOIN tags t ON t.id = pt.tag_id
              WHERE pt.product_id IN (' . implode(',', $in) . ') AND t.active = 1
              ORDER BY t.sort_order ASC',
            $params
        );

        $byProduct = [];
        foreach ($rows as $row) {
            $byProduct[(int) $row['product_id']][] = $row;
        }

        foreach ($products as $i => $product) {
            $products[$i]['tags'] = $byProduct[(int) $product['id']] ?? [];
        }

        return $products;
    }

    // =================================================================
    // Relación máquina ↔ repuesto
    // =================================================================

    /** Repuestos compatibles con una máquina del catálogo. @return array<int,array<string,mixed>> */
    public function compatibleParts(int $machineId, int $limit = 12): array
    {
        return Database::select(
            'SELECT ' . self::PUBLIC_COLUMNS . ', ' . self::EXTRA_COLUMNS . ', msp.recommended, msp.note
               FROM machine_spare_parts msp
               INNER JOIN products p ON p.id = msp.spare_part_id
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN machines m   ON m.product_id = p.id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
              WHERE msp.machine_id = :id AND p.active = 1 AND p.deleted_at IS NULL
              ORDER BY msp.recommended DESC, p.name ASC
              LIMIT ' . max(1, $limit),
            ['id' => $machineId]
        );
    }

    /** Máquinas del catálogo que usan un repuesto. @return array<int,array<string,mixed>> */
    public function compatibleMachines(int $partId, int $limit = 12): array
    {
        return Database::select(
            'SELECT ' . self::PUBLIC_COLUMNS . ', ' . self::EXTRA_COLUMNS . '
               FROM machine_spare_parts msp
               INNER JOIN products p ON p.id = msp.machine_id
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN machines m   ON m.product_id = p.id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
              WHERE msp.spare_part_id = :id AND p.active = 1 AND p.deleted_at IS NULL
              ORDER BY p.name ASC
              LIMIT ' . max(1, $limit),
            ['id' => $partId]
        );
    }

    /** Compatibilidad declarada (marca + modelo), exista o no la máquina. @return array<int,array<string,mixed>> */
    public function compatibilityList(int $partId): array
    {
        return Database::select(
            'SELECT spc.*, b.name AS brand_name, b.slug AS brand_slug
               FROM spare_part_compatibility spc
               LEFT JOIN brands b ON b.id = spc.brand_id
              WHERE spc.spare_part_id = :id
              ORDER BY b.name ASC, spc.model ASC',
            ['id' => $partId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function codes(int $partId): array
    {
        return Database::select(
            'SELECT spc.*, b.name AS brand_name
               FROM spare_part_codes spc
               LEFT JOIN brands b ON b.id = spc.brand_id
              WHERE spc.product_id = :id
              ORDER BY FIELD(spc.code_type,\'interno\',\'oem\',\'fabricante\',\'alternativo\',\'cruzado\'), spc.code ASC',
            ['id' => $partId]
        );
    }

    /**
     * Repuestos compatibles con un modelo escrito a mano (ej. "8FG25").
     *
     * @return array<int,array<string,mixed>>
     */
    public function partsForModel(string $model, int $limit = 24): array
    {
        return Database::select(
            'SELECT DISTINCT ' . self::PUBLIC_COLUMNS . ', ' . self::EXTRA_COLUMNS . '
               FROM spare_part_compatibility spc
               INNER JOIN products p ON p.id = spc.spare_part_id
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN machines m   ON m.product_id = p.id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
              WHERE spc.model LIKE :model AND p.active = 1 AND p.deleted_at IS NULL
              ORDER BY p.featured DESC, p.name ASC
              LIMIT ' . max(1, $limit),
            ['model' => '%' . $model . '%']
        );
    }

    // =================================================================
    // Bloques de la home / relacionados
    // =================================================================

    /** @return array<int,array<string,mixed>> */
    public function featured(string $type, int $limit = 6): array
    {
        $rows = Database::select(
            'SELECT ' . self::PUBLIC_COLUMNS . ', ' . self::EXTRA_COLUMNS . self::JOIN_BASE .
            ' WHERE p.type = :type AND p.active = 1 AND p.deleted_at IS NULL AND p.featured = 1
              ORDER BY p.updated_at DESC LIMIT ' . max(1, $limit),
            ['type' => $type]
        );

        return $this->attachTags($rows);
    }

    /** @return array<int,array<string,mixed>> */
    public function latest(string $type, int $limit = 6): array
    {
        $rows = Database::select(
            'SELECT ' . self::PUBLIC_COLUMNS . ', ' . self::EXTRA_COLUMNS . self::JOIN_BASE .
            ' WHERE p.type = :type AND p.active = 1 AND p.deleted_at IS NULL
              ORDER BY p.created_at DESC LIMIT ' . max(1, $limit),
            ['type' => $type]
        );

        return $this->attachTags($rows);
    }

    /** Máquinas similares: misma categoría, capacidad parecida. @return array<int,array<string,mixed>> */
    public function similar(array $product, int $limit = 4): array
    {
        $rows = Database::select(
            'SELECT ' . self::PUBLIC_COLUMNS . ', ' . self::EXTRA_COLUMNS . self::JOIN_BASE .
            ' WHERE p.id <> :id AND p.type = :type AND p.active = 1 AND p.deleted_at IS NULL
                AND (p.category_id = :cat OR p.brand_id = :brand)
              ORDER BY (p.category_id = :cat2) DESC, ABS(COALESCE(p.final_price,0) - :price) ASC
              LIMIT ' . max(1, $limit),
            [
                'id'    => (int) $product['id'],
                'type'  => (string) $product['type'],
                'cat'   => (int) ($product['category_id'] ?? 0),
                'cat2'  => (int) ($product['category_id'] ?? 0),
                'brand' => (int) ($product['brand_id'] ?? 0),
                'price' => (float) ($product['final_price'] ?? 0),
            ]
        );

        return $this->attachTags($rows);
    }

    /** @param array<int,int> $ids @return array<int,array<string,mixed>> */
    public function findMany(array $ids, bool $internal = false): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $in     = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $in[] = ':id' . $i;
            $params['id' . $i] = $id;
        }

        $columns = $internal ? self::INTERNAL_COLUMNS : self::PUBLIC_COLUMNS;

        $rows = Database::select(
            'SELECT ' . $columns . ', ' . self::EXTRA_COLUMNS . self::JOIN_BASE .
            ' WHERE p.id IN (' . implode(',', $in) . ') AND p.deleted_at IS NULL' .
            ($internal ? '' : ' AND p.active = 1'),
            $params
        );

        // Se respeta el orden en que vinieron los IDs
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    // =================================================================
    // Métricas
    // =================================================================

    public function registerView(int $productId): void
    {
        Database::execute('UPDATE products SET views = views + 1 WHERE id = :id', ['id' => $productId]);
    }

    public function incrementCounter(int $productId, string $column): void
    {
        if (!in_array($column, ['inquiries_count', 'quotes_count'], true)) {
            return;
        }
        Database::execute(
            sprintf('UPDATE products SET %s = %s + 1 WHERE id = :id', $column, $column),
            ['id' => $productId]
        );
    }

    /** Marcas presentes en el catálogo de un tipo. @return array<int,array<string,mixed>> */
    public function availableBrands(string $type): array
    {
        return Database::select(
            'SELECT b.id, b.name, b.slug, COUNT(p.id) AS total
               FROM brands b
               INNER JOIN products p ON p.brand_id = b.id AND p.type = :type AND p.active = 1 AND p.deleted_at IS NULL
              WHERE b.active = 1
              GROUP BY b.id, b.name, b.slug
              ORDER BY b.name ASC',
            ['type' => $type]
        );
    }

    /** @return array{min:float,max:float} */
    public function priceRange(string $type): array
    {
        $row = Database::selectOne(
            'SELECT MIN(final_price) AS min_price, MAX(final_price) AS max_price
               FROM products WHERE type = :type AND active = 1 AND deleted_at IS NULL AND final_price > 0',
            ['type' => $type]
        );

        return [
            'min' => (float) ($row['min_price'] ?? 0),
            'max' => (float) ($row['max_price'] ?? 0),
        ];
    }

    public function nextCode(string $type): string
    {
        $prefix = $type === 'machine' ? 'AE-' : 'REP-';
        $last   = (string) Database::scalar(
            'SELECT code FROM products WHERE type = :type AND code LIKE :prefix ORDER BY id DESC LIMIT 1',
            ['type' => $type, 'prefix' => $prefix . '%']
        );

        $number = 1;
        if ($last !== '' && preg_match('/(\d+)$/', $last, $m)) {
            $number = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
