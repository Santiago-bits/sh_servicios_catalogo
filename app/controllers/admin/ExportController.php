<?php
/**
 * ARCHIVO: app/controllers/admin/ExportController.php
 * ---------------------------------------------------------------------
 * Exportación a CSV/Excel y generador de catálogo PDF.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\ExportService;
use App\Services\PdfService;
use Core\Auth;
use Core\Request;

class ExportController extends AdminController
{
    public function index(): void
    {
        // Sólo los conjuntos de datos que tienen sentido en este panel
        // (catálogo + consultas + auditoría). Los de cotizaciones/movimientos,
        // que dependen de secciones que no están activas, se omiten.
        $visible  = ['maquinaria', 'repuestos', 'precios', 'consultas', 'auditoria'];
        $datasets = array_intersect_key(ExportService::DATASETS, array_flip($visible));

        $this->view('admin/exports/index', [
            'pageTitle'  => 'Exportar · Panel',
            'adminTitle' => 'Exportar',
            'robots'     => 'noindex, nofollow',
            'datasets'   => $datasets,
            'canSeeCost' => Auth::canSeeCost(),
        ]);
    }

    public function download(string $dataset, string $format): void
    {
        if (!isset(ExportService::DATASETS[$dataset])) {
            $this->abort(404, 'Conjunto de datos inexistente.');
        }
        if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $this->abort(404, 'Formato no soportado.');
        }

        $data     = ExportService::dataset($dataset);
        $filename = $dataset . '-' . date('Ymd-Hi');

        AuditService::log('export', 'data', null, null, 'Exportación de ' . $dataset . ' (' . $format . ')');

        if ($format === 'pdf') {
            // El PDF sólo muestra las primeras columnas (las clave): con 40+
            // columnas quedaría ilegible. Para el detalle completo, Excel/CSV.
            $cols    = $data['pdf_cols'] ?? count($data['headers']);
            $headers = array_map([ExportService::class, 'ascii'], array_slice($data['headers'], 0, $cols));
            $pdfRows = array_map(
                static fn (array $r): array => array_map([ExportService::class, 'ascii'], array_slice($r, 0, $cols)),
                $data['rows']
            );

            PdfService::table(ExportService::ascii($data['title']), $headers, $pdfRows)
                ->stream(ExportService::ascii($filename) . '.pdf', true);
        }

        if ($format === 'xlsx') {
            ExportService::toXlsx($data, $filename);
        }

        ExportService::toCsv($data, $filename);
    }

    // ----------------------------------------------------------------
    // Catálogo PDF
    // ----------------------------------------------------------------

    public function catalogForm(): void
    {
        $this->view('admin/exports/catalog', [
            'pageTitle'  => 'Generar catálogo PDF · Panel',
            'adminTitle' => 'Catálogo PDF',
            'robots'     => 'noindex, nofollow',
            'categories' => (new Category())->withParent(),
            'brands'     => (new Brand())->active(),
        ]);
    }

    public function catalogPdf(): void
    {
        $type = Request::post('tipo') === 'spare_part' ? 'spare_part' : 'machine';

        $filters = [
            'categoria'  => Request::int('categoria') ?: null,
            'marca'      => Request::int('marca') ?: null,
            'destacados' => Request::bool('solo_destacados') ? 1 : null,
            'orden'      => (string) Request::post('orden', 'az'),
        ];

        $result = (new Product())->catalog($type, $filters, 1, 200);

        $title = (string) Request::post('titulo', '') ?: ($type === 'machine' ? 'Catálogo de maquinaria' : 'Catálogo de repuestos');

        AuditService::log('export', 'data', null, null, 'Catálogo PDF generado: ' . $title . ' (' . count($result['data']) . ' productos)');

        PdfService::catalog($result['data'], $title, Request::bool('con_precios', true))
            ->stream('Catalogo-' . slugify($title) . '-' . date('Ymd') . '.pdf', true);
    }
}
