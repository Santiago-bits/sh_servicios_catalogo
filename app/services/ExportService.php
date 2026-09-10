<?php
/**
 * ARCHIVO: app/services/ExportService.php
 * ---------------------------------------------------------------------
 * Exportación de datos a CSV y Excel (.xlsx).
 *
 * IMPORTANTE: las columnas de costo y ganancia sólo se incluyen si el
 * usuario tiene el permiso prices.view_cost.
 */

declare(strict_types=1);

namespace App\Services;

use Core\Auth;
use Core\Database;
use Lib\Xlsx;

final class ExportService
{
    public const DATASETS = [
        'maquinaria'   => 'Maquinaria',
        'repuestos'    => 'Repuestos',
        'precios'      => 'Lista de precios',
        'consultas'    => 'Consultas',
        'cotizaciones' => 'Cotizaciones',
        'auditoria'    => 'Auditoría',
    ];

    /**
     * @return array{title:string,headers:array<int,string>,rows:array<int,array<int,mixed>>,pdf_cols?:int}
     */
    public static function dataset(string $dataset): array
    {
        $withCost = Auth::canSeeCost();

        return match ($dataset) {
            'maquinaria'   => self::machines($withCost),
            'repuestos'    => self::parts($withCost),
            'precios'      => self::prices($withCost),
            'consultas'    => self::inquiries(),
            'cotizaciones' => self::quotes(),
            'auditoria'    => self::audit(),
            default        => ['title' => 'Datos', 'headers' => [], 'rows' => []],
        };
    }

    // ----------------------------------------------------------------
    // Ayudas de formato (para que la salida sea legible)
    // ----------------------------------------------------------------

    /** Número o cadena vacía (nunca "0" cuando en realidad no hay dato). */
    private static function num(mixed $v): float|string
    {
        return ($v === null || $v === '') ? '' : (float) $v;
    }

    private static function int(mixed $v): int|string
    {
        return ($v === null || $v === '') ? '' : (int) $v;
    }

    /** "nuevo" → "Nuevo". */
    private static function cap(?string $v): string
    {
        $v = trim((string) $v);
        return $v === '' ? '' : mb_strtoupper(mb_substr($v, 0, 1)) . mb_substr($v, 1);
    }

    private static function yesNo(mixed $v): string
    {
        return (int) $v === 1 ? 'Sí' : 'No';
    }

    /** Texto plano, sin HTML ni saltos de línea, recortado. */
    private static function text(?string $v, int $max = 500): string
    {
        $t = trim((string) preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        return mb_strlen($t) > $max ? mb_substr($t, 0, $max) . '…' : $t;
    }

    // ----------------------------------------------------------------
    // Conjuntos de datos
    // ----------------------------------------------------------------

    private static function machines(bool $withCost): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, b.name AS brand, c.name AS category, c.slug AS category_slug, p.slug,
                    p.currency, p.cost_price, p.profit_percent, p.final_price, p.offer_price, p.is_offer,
                    p.price_visible, p.availability, p.featured, p.is_new, p.active,
                    p.short_description, p.description, p.views, p.created_at, p.updated_at,
                    m.model, m.year, m.hours, m.serial_number, m.condition_type, m.fuel, m.engine,
                    m.power_hp, m.transmission, m.capacity_kg, m.lift_height_mm, m.closed_height_mm,
                    m.weight_kg, m.length_mm, m.width_mm, m.turn_radius_mm, m.battery, m.voltage,
                    m.mast_type, m.tire_type, m.fork_size, m.location, m.warranty
               FROM products p
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN machines m   ON m.product_id = p.id
              WHERE p.type = \'machine\' AND p.deleted_at IS NULL
              ORDER BY p.code ASC'
        );

        $map = static function (array $r) use ($withCost): array {
            $out = [
                'Código'        => $r['code'],
                'Nombre'        => $r['name'],
                'Marca'         => $r['brand'],
                'Modelo'        => $r['model'],
                'Categoría'     => $r['category'],
                'Condición'     => self::cap($r['condition_type']),
                'Estado'        => $r['availability'] !== null ? availability_badge((string) $r['availability'])['label'] : '',
                'Moneda'        => $r['currency'],
                'Precio'        => self::num($r['final_price']),
                'Precio oferta' => self::num($r['offer_price']),
            ];
            if ($withCost) {
                $out['Costo']      = self::num($r['cost_price']);
                $out['Ganancia %'] = self::num($r['profit_percent']);
            }
            $out += [
                'Año'                   => self::int($r['year']),
                'Horas'                 => self::int($r['hours']),
                'Combustible'           => fuel_label($r['fuel']),
                'Capacidad (kg)'        => self::num($r['capacity_kg']),
                'Altura elevación (mm)' => self::int($r['lift_height_mm']),
                'Altura plegada (mm)'   => self::int($r['closed_height_mm']),
                'Motor'                 => $r['engine'],
                'Potencia (HP)'         => self::num($r['power_hp']),
                'Transmisión'           => $r['transmission'],
                'Peso (kg)'             => self::num($r['weight_kg']),
                'Largo (mm)'            => self::int($r['length_mm']),
                'Ancho (mm)'            => self::int($r['width_mm']),
                'Radio de giro (mm)'    => self::int($r['turn_radius_mm']),
                'Batería'               => $r['battery'],
                'Voltaje'               => $r['voltage'],
                'Tipo de mástil'        => $r['mast_type'],
                'Tipo de rueda'         => $r['tire_type'],
                'Tamaño de uña'         => $r['fork_size'],
                'Garantía'              => $r['warranty'],
                'Nro. de serie'         => $r['serial_number'],
                'Ubicación'             => $r['location'],
                'En oferta'             => self::yesNo($r['is_offer']),
                'Precio visible'        => self::yesNo($r['price_visible']),
                'Destacado'             => self::yesNo($r['featured']),
                'Nuevo'                 => self::yesNo($r['is_new']),
                'Activo'                => self::yesNo($r['active']),
                'Visitas'               => (int) $r['views'],
                'Resumen'               => self::text($r['short_description'], 220),
                'Descripción'           => self::text($r['description']),
                'Alta'                  => date_es((string) $r['created_at'], true),
                'Últ. modificación'     => date_es((string) $r['updated_at'], true),
                'Ficha'                 => ($r['category_slug'] && $r['slug'])
                    ? url('maquinaria/' . $r['category_slug'] . '/' . $r['slug'])
                    : '',
            ];
            return $out;
        };

        $blank   = $rows[0] ?? array_fill_keys([
            'code', 'name', 'brand', 'category', 'category_slug', 'slug', 'currency', 'cost_price',
            'profit_percent', 'final_price', 'offer_price', 'is_offer', 'price_visible', 'availability',
            'featured', 'is_new', 'active', 'short_description', 'description', 'views', 'created_at',
            'updated_at', 'model', 'year', 'hours', 'serial_number', 'condition_type', 'fuel', 'engine',
            'power_hp', 'transmission', 'capacity_kg', 'lift_height_mm', 'closed_height_mm', 'weight_kg',
            'length_mm', 'width_mm', 'turn_radius_mm', 'battery', 'voltage', 'mast_type', 'tire_type',
            'fork_size', 'location', 'warranty',
        ], null);
        $headers = array_keys($map($blank));
        $data    = array_map(static fn (array $r): array => array_values($map($r)), $rows);

        return ['title' => 'Maquinaria', 'headers' => $headers, 'rows' => $data, 'pdf_cols' => $withCost ? 12 : 10];
    }

    private static function parts(bool $withCost): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, b.name AS brand, c.name AS category, c.slug AS category_slug, p.slug,
                    p.currency, p.cost_price, p.profit_percent, p.final_price, p.offer_price, p.is_offer,
                    p.price_visible, p.availability, p.featured, p.is_new, p.active,
                    p.short_description, p.description, p.views, p.created_at, p.updated_at,
                    sp.oem_code, sp.manufacturer_code, sp.manufacturer, sp.origin, sp.unit, sp.weight_kg,
                    (SELECT COUNT(*) FROM spare_part_compatibility s WHERE s.spare_part_id = p.id) AS compat_count,
                    (SELECT GROUP_CONCAT(DISTINCT s2.model ORDER BY s2.model SEPARATOR \' | \')
                       FROM spare_part_compatibility s2 WHERE s2.spare_part_id = p.id) AS compat_list
               FROM products p
               LEFT JOIN brands b       ON b.id = p.brand_id
               LEFT JOIN categories c   ON c.id = p.category_id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
              WHERE p.type = \'spare_part\' AND p.deleted_at IS NULL
              ORDER BY p.code ASC'
        );

        $map = static function (array $r) use ($withCost): array {
            $out = [
                'Código'            => $r['code'],
                'Nombre'            => $r['name'],
                'Marca'             => $r['brand'],
                'Categoría'         => $r['category'],
                'Fabricante'        => $r['manufacturer'],
                'Código OEM'        => $r['oem_code'],
                'Código fabricante' => $r['manufacturer_code'],
                'Origen'            => self::cap($r['origin']),
                'Unidad'            => $r['unit'],
                'Peso (kg)'         => self::num($r['weight_kg']),
                'Moneda'            => $r['currency'],
                'Precio'            => self::num($r['final_price']),
                'Precio oferta'     => self::num($r['offer_price']),
            ];
            if ($withCost) {
                $out['Costo']      = self::num($r['cost_price']);
                $out['Ganancia %'] = self::num($r['profit_percent']);
            }
            $out += [
                'En oferta'         => self::yesNo($r['is_offer']),
                'Precio visible'    => self::yesNo($r['price_visible']),
                'Estado'            => $r['availability'] !== null ? availability_badge((string) $r['availability'])['label'] : '',
                'Destacado'         => self::yesNo($r['featured']),
                'Nuevo'             => self::yesNo($r['is_new']),
                'Activo'            => self::yesNo($r['active']),
                'Compatibilidades'  => (int) $r['compat_count'],
                'Compatible con'    => (string) $r['compat_list'],
                'Visitas'           => (int) $r['views'],
                'Resumen'           => self::text($r['short_description'], 220),
                'Descripción'       => self::text($r['description']),
                'Alta'              => date_es((string) $r['created_at'], true),
                'Últ. modificación' => date_es((string) $r['updated_at'], true),
                'Ficha'             => ($r['category_slug'] && $r['slug'])
                    ? url('repuestos/' . $r['category_slug'] . '/' . $r['slug'])
                    : '',
            ];
            return $out;
        };

        $blank   = $rows[0] ?? array_fill_keys([
            'code', 'name', 'brand', 'category', 'category_slug', 'slug', 'currency', 'cost_price',
            'profit_percent', 'final_price', 'offer_price', 'is_offer', 'price_visible', 'availability',
            'featured', 'is_new', 'active', 'short_description', 'description', 'views', 'created_at',
            'updated_at', 'oem_code', 'manufacturer_code', 'manufacturer', 'origin', 'unit', 'weight_kg',
            'compat_count', 'compat_list',
        ], null);
        $headers = array_keys($map($blank));
        $data    = array_map(static fn (array $r): array => array_values($map($r)), $rows);

        return ['title' => 'Repuestos', 'headers' => $headers, 'rows' => $data, 'pdf_cols' => $withCost ? 15 : 13];
    }

    private static function prices(bool $withCost): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, p.type, b.name AS brand, p.cost_price, p.profit_percent,
                    p.profit_amount, p.final_price, p.offer_price, p.is_offer, p.currency,
                    p.price_visible, p.availability, p.active, p.price_updated_at
               FROM products p LEFT JOIN brands b ON b.id = p.brand_id
              WHERE p.deleted_at IS NULL ORDER BY p.type ASC, p.code ASC'
        );

        $headers = ['Código', 'Nombre', 'Tipo', 'Marca'];
        if ($withCost) {
            $headers = array_merge($headers, ['Costo', 'Ganancia %', 'Ganancia $']);
        }
        $headers = array_merge($headers, ['Precio final', 'Precio oferta', 'En oferta', 'Moneda',
                                          'Precio visible', 'Estado', 'Activo', 'Actualizado']);

        $data = [];
        foreach ($rows as $row) {
            $line = [$row['code'], $row['name'], $row['type'] === 'machine' ? 'Maquinaria' : 'Repuesto', $row['brand']];

            if ($withCost) {
                $line[] = self::num($row['cost_price']);
                $line[] = self::num($row['profit_percent']);
                $line[] = self::num($row['profit_amount']);
            }

            $line[] = self::num($row['final_price']);
            $line[] = self::num($row['offer_price']);
            $line[] = self::yesNo($row['is_offer']);
            $line[] = $row['currency'];
            $line[] = self::yesNo($row['price_visible']);
            $line[] = $row['availability'] !== null ? availability_badge((string) $row['availability'])['label'] : '';
            $line[] = self::yesNo($row['active']);
            $line[] = date_es((string) $row['price_updated_at'], true);

            $data[] = $line;
        }

        return ['title' => 'Precios', 'headers' => $headers, 'rows' => $data];
    }

    private static function inquiries(): array
    {
        $rows = Database::select(
            'SELECT i.created_at, i.name, i.company, i.email, i.phone, p.code AS producto,
                    i.subject, i.message, i.channel, i.status
               FROM inquiries i LEFT JOIN products p ON p.id = i.product_id
              ORDER BY i.created_at DESC'
        );

        $data = array_map(static fn (array $r): array => [
            date_es((string) $r['created_at'], true), $r['name'], $r['company'], $r['email'], $r['phone'],
            $r['producto'], self::text($r['subject'], 160), self::text($r['message'], 400),
            self::cap($r['channel']), inquiry_status_badge((string) $r['status'])['label'],
        ], $rows);

        return [
            'title'   => 'Consultas',
            'headers' => ['Fecha', 'Nombre', 'Empresa', 'Email', 'Teléfono', 'Producto', 'Asunto', 'Mensaje', 'Canal', 'Estado'],
            'rows'    => $data,
        ];
    }

    private static function quotes(): array
    {
        $rows = Database::select(
            'SELECT q.number, q.created_at, q.customer_name, q.customer_company, q.customer_email,
                    q.subtotal, q.discount_amount, q.total, q.currency, q.status, q.valid_until, u.name AS usuario,
                    (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id = q.id) AS items
               FROM quotes q LEFT JOIN users u ON u.id = q.user_id
              ORDER BY q.created_at DESC'
        );

        $data = array_map(static fn (array $r): array => [
            $r['number'], date_es((string) $r['created_at']), $r['customer_name'], $r['customer_company'],
            $r['customer_email'], (int) $r['items'], (float) $r['subtotal'], (float) $r['discount_amount'],
            (float) $r['total'], $r['currency'], quote_status_badge((string) $r['status'])['label'],
            date_es((string) $r['valid_until']), $r['usuario'],
        ], $rows);

        return [
            'title'   => 'Cotizaciones',
            'headers' => ['Número', 'Fecha', 'Cliente', 'Empresa', 'Email', 'Ítems', 'Subtotal', 'Descuento',
                          'Total', 'Moneda', 'Estado', 'Validez', 'Asesor'],
            'rows'    => $data,
        ];
    }

    private static function audit(): array
    {
        $rows = Database::select(
            'SELECT created_at, user_name, action, module, entity_type, entity_id, description, ip
               FROM activity_logs ORDER BY created_at DESC LIMIT 5000'
        );

        $data = array_map(static fn (array $r): array => [
            date_es((string) $r['created_at'], true), $r['user_name'], $r['action'], $r['module'],
            $r['entity_type'], $r['entity_id'] !== null ? (int) $r['entity_id'] : '', $r['description'], $r['ip'],
        ], $rows);

        return [
            'title'   => 'Auditoría',
            'headers' => ['Fecha', 'Usuario', 'Acción', 'Módulo', 'Entidad', 'ID', 'Descripción', 'IP'],
            'rows'    => $data,
        ];
    }

    // ----------------------------------------------------------------
    // Formatos de salida
    // ----------------------------------------------------------------

    /**
     * Saca tildes y cualquier caracter fuera del ASCII imprimible. Así el
     * archivo se abre igual en cualquier Excel/PC sin "letras raras",
     * pase lo que pase con la codificación.
     */
    public static function ascii(mixed $value): string
    {
        $s = (string) $value;

        $s = strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u', 'ç' => 'c', 'Ç' => 'C',
            'º' => '', 'ª' => '', '°' => '', '¿' => '', '¡' => '',
            '–' => '-', '—' => '-', '‘' => "'", '’' => "'", '“' => '"', '”' => '"', '…' => '...',
            '€' => 'EUR', '·' => '-', "\u{00A0}" => ' ',
        ]);

        // Cualquier byte que quede fuera de ASCII imprimible / tab / salto.
        return (string) preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $s);
    }

    /**
     * @param array{headers:array<int,string>,rows:array<int,array<int,mixed>>} $data
     */
    public static function toCsv(array $data, string $filename): never
    {
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . self::ascii($filename) . '.csv"');
            header('Cache-Control: no-store');
        }

        $output = fopen('php://output', 'wb');

        // "sep=;" para que Excel separe en columnas (sin esto mete todo en
        // la columna A en muchas PC). El contenido va sin tildes, así que
        // no hace falta BOM.
        fwrite($output, "sep=;\r\n");

        fputcsv($output, array_map([self::class, 'ascii'], $data['headers']), ';', '"', '\\');
        foreach ($data['rows'] as $row) {
            fputcsv($output, array_map([self::class, 'ascii'], $row), ';', '"', '\\');
        }

        fclose($output);
        exit;
    }

    /** ¿El servidor puede generar archivos .xlsx? (necesita la extensión zip) */
    public static function canExportXlsx(): bool
    {
        return class_exists('ZipArchive');
    }

    /** @param array{title:string,headers:array<int,string>,rows:array<int,array<int,mixed>>} $data */
    public static function toXlsx(array $data, string $filename): never
    {
        // Sin la extensión zip (pasa en algunos hostings gratuitos) el .xlsx
        // no se puede armar: se entrega CSV, que Excel abre igual.
        if (!self::canExportXlsx()) {
            self::toCsv($data, $filename);
        }

        $xlsx = new Xlsx(self::ascii($data['title']));
        $xlsx->setHeaders(array_map([self::class, 'ascii'], $data['headers']));
        $xlsx->addRows(array_map(
            static fn (array $r): array => array_map(
                static fn (mixed $v): mixed => is_string($v) ? self::ascii($v) : $v,
                $r
            ),
            $data['rows']
        ));

        AuditService::log('export', 'data', null, null, 'Exportación de ' . $data['title'] . ' a Excel');

        $xlsx->stream(self::ascii($filename) . '.xlsx');
    }
}
