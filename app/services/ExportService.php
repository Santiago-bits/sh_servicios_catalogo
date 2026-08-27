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
        'stock'        => 'Stock de repuestos',
        'precios'      => 'Lista de precios',
        'consultas'    => 'Consultas',
        'cotizaciones' => 'Cotizaciones',
        'movimientos'  => 'Movimientos de stock',
        'auditoria'    => 'Auditoría',
    ];

    /**
     * @return array{title:string,headers:array<int,string>,rows:array<int,array<int,mixed>>}
     */
    public static function dataset(string $dataset): array
    {
        $withCost = Auth::canSeeCost();

        return match ($dataset) {
            'maquinaria'   => self::machines($withCost),
            'repuestos'    => self::parts($withCost),
            'stock'        => self::stock(),
            'precios'      => self::prices($withCost),
            'consultas'    => self::inquiries(),
            'cotizaciones' => self::quotes(),
            'movimientos'  => self::movements(),
            'auditoria'    => self::audit(),
            default        => ['title' => 'Datos', 'headers' => [], 'rows' => []],
        };
    }

    // ----------------------------------------------------------------
    // Conjuntos de datos
    // ----------------------------------------------------------------

    private static function machines(bool $withCost): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, b.name AS brand, m.model, c.name AS category, m.year, m.hours,
                    m.fuel, m.capacity_kg, m.lift_height_mm, m.condition_type, m.location,
                    p.cost_price, p.profit_percent, p.final_price, p.currency, p.availability, p.active
               FROM products p
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
               LEFT JOIN machines m   ON m.product_id = p.id
              WHERE p.type = \'machine\' AND p.deleted_at IS NULL
              ORDER BY p.code ASC'
        );

        $headers = ['Código', 'Nombre', 'Marca', 'Modelo', 'Categoría', 'Año', 'Horas', 'Combustible',
                    'Capacidad (kg)', 'Altura (mm)', 'Condición', 'Ubicación'];

        if ($withCost) {
            $headers[] = 'Costo';
            $headers[] = 'Ganancia %';
        }

        $headers = array_merge($headers, ['Precio final', 'Moneda', 'Estado', 'Activo']);

        $data = [];
        foreach ($rows as $row) {
            $line = [
                $row['code'], $row['name'], $row['brand'], $row['model'], $row['category'],
                $row['year'] !== null ? (int) $row['year'] : '',
                $row['hours'] !== null ? (int) $row['hours'] : '',
                fuel_label($row['fuel']),
                $row['capacity_kg'] !== null ? (float) $row['capacity_kg'] : '',
                $row['lift_height_mm'] !== null ? (int) $row['lift_height_mm'] : '',
                $row['condition_type'], $row['location'],
            ];

            if ($withCost) {
                $line[] = (float) $row['cost_price'];
                $line[] = (float) $row['profit_percent'];
            }

            $line[] = (float) $row['final_price'];
            $line[] = $row['currency'];
            $line[] = availability_badge((string) $row['availability'])['label'];
            $line[] = (int) $row['active'] === 1 ? 'Sí' : 'No';

            $data[] = $line;
        }

        return ['title' => 'Maquinaria', 'headers' => $headers, 'rows' => $data];
    }

    private static function parts(bool $withCost): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, b.name AS brand, c.name AS category, sp.oem_code, sp.manufacturer_code,
                    sp.manufacturer, sp.origin, p.cost_price, p.profit_percent, p.final_price, p.currency,
                    p.stock, p.stock_reserved, p.stock_min, sp.shelf, sp.position, p.active,
                    (SELECT COUNT(*) FROM spare_part_compatibility s WHERE s.spare_part_id = p.id) AS compatibilidades
               FROM products p
               LEFT JOIN brands b       ON b.id = p.brand_id
               LEFT JOIN categories c   ON c.id = p.category_id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
              WHERE p.type = \'spare_part\' AND p.deleted_at IS NULL
              ORDER BY p.code ASC'
        );

        $headers = ['Código', 'Nombre', 'Marca', 'Categoría', 'Código OEM', 'Código fabricante', 'Fabricante', 'Origen'];

        if ($withCost) {
            $headers[] = 'Costo';
            $headers[] = 'Ganancia %';
        }

        $headers = array_merge($headers, ['Precio final', 'Moneda', 'Stock', 'Reservado', 'Stock mínimo',
                                          'Estantería', 'Posición', 'Compatibilidades', 'Activo']);

        $data = [];
        foreach ($rows as $row) {
            $line = [
                $row['code'], $row['name'], $row['brand'], $row['category'],
                $row['oem_code'], $row['manufacturer_code'], $row['manufacturer'], $row['origin'],
            ];

            if ($withCost) {
                $line[] = (float) $row['cost_price'];
                $line[] = (float) $row['profit_percent'];
            }

            $line = array_merge($line, [
                (float) $row['final_price'], $row['currency'],
                (int) $row['stock'], (int) $row['stock_reserved'], (int) $row['stock_min'],
                $row['shelf'], $row['position'], (int) $row['compatibilidades'],
                (int) $row['active'] === 1 ? 'Sí' : 'No',
            ]);

            $data[] = $line;
        }

        return ['title' => 'Repuestos', 'headers' => $headers, 'rows' => $data];
    }

    private static function stock(): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, c.name AS category, p.stock, p.stock_reserved,
                    (p.stock - p.stock_reserved) AS disponible, p.stock_min,
                    w.name AS deposito, sp.sector, sp.shelf, sp.position
               FROM products p
               LEFT JOIN categories c   ON c.id = p.category_id
               LEFT JOIN spare_parts sp ON sp.product_id = p.id
               LEFT JOIN warehouses w   ON w.id = sp.warehouse_id
              WHERE p.type = \'spare_part\' AND p.deleted_at IS NULL
              ORDER BY (p.stock - p.stock_reserved) ASC'
        );

        $data = array_map(static fn (array $r): array => [
            $r['code'], $r['name'], $r['category'],
            (int) $r['stock'], (int) $r['stock_reserved'], (int) $r['disponible'], (int) $r['stock_min'],
            $r['deposito'], $r['sector'], $r['shelf'], $r['position'],
        ], $rows);

        return [
            'title'   => 'Stock',
            'headers' => ['Código', 'Nombre', 'Categoría', 'Stock', 'Reservado', 'Disponible', 'Mínimo',
                          'Depósito', 'Sector', 'Estantería', 'Posición'],
            'rows'    => $data,
        ];
    }

    private static function prices(bool $withCost): array
    {
        $rows = Database::select(
            'SELECT p.code, p.name, p.type, b.name AS brand, p.cost_price, p.profit_percent,
                    p.profit_amount, p.final_price, p.offer_price, p.currency, p.price_updated_at
               FROM products p LEFT JOIN brands b ON b.id = p.brand_id
              WHERE p.deleted_at IS NULL ORDER BY p.type ASC, p.code ASC'
        );

        $headers = ['Código', 'Nombre', 'Tipo', 'Marca'];
        if ($withCost) {
            $headers = array_merge($headers, ['Costo', 'Ganancia %', 'Ganancia $']);
        }
        $headers = array_merge($headers, ['Precio final', 'Precio oferta', 'Moneda', 'Actualizado']);

        $data = [];
        foreach ($rows as $row) {
            $line = [$row['code'], $row['name'], $row['type'] === 'machine' ? 'Maquinaria' : 'Repuesto', $row['brand']];

            if ($withCost) {
                $line[] = (float) $row['cost_price'];
                $line[] = (float) $row['profit_percent'];
                $line[] = (float) $row['profit_amount'];
            }

            $line[] = (float) $row['final_price'];
            $line[] = $row['offer_price'] !== null ? (float) $row['offer_price'] : '';
            $line[] = $row['currency'];
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
            $r['producto'], $r['subject'], str_limit((string) $r['message'], 300, ''),
            $r['channel'], inquiry_status_badge((string) $r['status'])['label'],
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

    private static function movements(): array
    {
        $rows = Database::select(
            'SELECT sm.created_at, p.code, p.name, sm.type, sm.quantity, sm.stock_before, sm.stock_after,
                    sm.reason, sm.reference, u.name AS usuario
               FROM stock_movements sm
               INNER JOIN products p ON p.id = sm.product_id
               LEFT JOIN users u ON u.id = sm.user_id
              ORDER BY sm.created_at DESC LIMIT 5000'
        );

        $data = array_map(static fn (array $r): array => [
            date_es((string) $r['created_at'], true), $r['code'], $r['name'],
            StockService::TYPES[$r['type']] ?? $r['type'],
            (int) $r['quantity'], (int) $r['stock_before'], (int) $r['stock_after'],
            $r['reason'], $r['reference'], $r['usuario'],
        ], $rows);

        return [
            'title'   => 'Movimientos de stock',
            'headers' => ['Fecha', 'Código', 'Producto', 'Tipo', 'Cantidad', 'Stock anterior', 'Stock posterior',
                          'Motivo', 'Referencia', 'Usuario'],
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

    /** @param array{headers:array<int,string>,rows:array<int,array<int,mixed>>} $data */
    public static function toCsv(array $data, string $filename): never
    {
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            header('Cache-Control: no-store');
        }

        $output = fopen('php://output', 'wb');

        // BOM para que Excel reconozca UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, $data['headers'], ';', '"', '\\');
        foreach ($data['rows'] as $row) {
            fputcsv($output, $row, ';', '"', '\\');
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

        $xlsx = new Xlsx($data['title']);
        $xlsx->setHeaders($data['headers']);
        $xlsx->addRows($data['rows']);

        AuditService::log('export', 'data', null, null, 'Exportación de ' . $data['title'] . ' a Excel');

        $xlsx->stream($filename . '.xlsx');
    }
}
