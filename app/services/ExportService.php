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
     * @return array{title:string,headers:array<int,string>,rows:array<int,array<int,mixed>>}
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
                    sp.manufacturer, sp.origin, p.cost_price, p.profit_percent, p.final_price, p.currency, p.active,
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

        $headers = array_merge($headers, ['Precio final', 'Moneda', 'Compatibilidades', 'Activo']);

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
                (int) $row['compatibilidades'],
                (int) $row['active'] === 1 ? 'Sí' : 'No',
            ]);

            $data[] = $line;
        }

        return ['title' => 'Repuestos', 'headers' => $headers, 'rows' => $data];
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

        // BOM para que Excel reconozca UTF-8; "sep=;" para que separe en
        // columnas (sin esto Excel mete todo en la columna A en muchas PC).
        fwrite($output, "\xEF\xBB\xBF");
        fwrite($output, "sep=;\r\n");

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
