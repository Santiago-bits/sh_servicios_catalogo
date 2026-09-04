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
        // Sección en desarrollo: se muestra un cartel en vez del exportador.
        $this->view('admin/wip', [
            'pageTitle'  => 'Exportar · Panel',
            'adminTitle' => 'Exportar',
            'robots'     => 'noindex, nofollow',
            'wipTitle'   => 'Exportar productos',
            'wipText'    => 'Estamos trabajando en la exportacion a Excel/CSV y el catalogo en PDF. Va a estar disponible proximamente.',
        ]);
    }

    public function download(string $dataset, string $format): void
    {
        if (!isset(ExportService::DATASETS[$dataset])) {
            $this->abort(404, 'Conjunto de datos inexistente.');
        }
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            $this->abort(404, 'Formato no soportado.');
        }

        $data     = ExportService::dataset($dataset);
        $filename = $dataset . '-' . date('Ymd-Hi');

        AuditService::log('export', 'data', null, null, 'Exportación de ' . $dataset . ' (' . $format . ')');

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
            'con_stock'  => Request::bool('solo_con_stock') ? 1 : null,
            'orden'      => (string) Request::post('orden', 'az'),
        ];

        $result = (new Product())->catalog($type, $filters, 1, 200);

        $title = (string) Request::post('titulo', '') ?: ($type === 'machine' ? 'Catálogo de maquinaria' : 'Catálogo de repuestos');

        AuditService::log('export', 'data', null, null, 'Catálogo PDF generado: ' . $title . ' (' . count($result['data']) . ' productos)');

        PdfService::catalog($result['data'], $title, Request::bool('con_precios', true))
            ->stream('Catalogo-' . slugify($title) . '-' . date('Ymd') . '.pdf', true);
    }
}
