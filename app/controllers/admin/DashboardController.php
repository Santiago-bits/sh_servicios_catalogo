<?php
/**
 * ARCHIVO: app/controllers/admin/DashboardController.php
 * ---------------------------------------------------------------------
 * Panel de inicio: resumen general del catálogo, cosas para revisar
 * (sin foto, sin precio…) y últimas consultas.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Inquiry;
use Core\Database;

class DashboardController extends AdminController
{
    /**
     * Archivo de texto (HTML plano) con las notas de las actualizaciones.
     * Se edita a mano y se sube con el resto de la carpeta: sin base de
     * datos ni formulario en el panel.
     */
    private const NEWS_FILE = BASE_PATH . '/content/novedades.html';

    public function index(): void
    {
        $n = static fn (string $sql): int => (int) Database::scalar($sql);

        $activeProduct = "active = 1 AND deleted_at IS NULL";
        $anyProduct    = "deleted_at IS NULL";

        $stats = [
            'machines'        => $n("SELECT COUNT(*) FROM products WHERE type='machine' AND $anyProduct"),
            'machines_active' => $n("SELECT COUNT(*) FROM products WHERE type='machine' AND $activeProduct"),
            'parts'           => $n("SELECT COUNT(*) FROM products WHERE type='spare_part' AND $anyProduct"),
            'parts_active'    => $n("SELECT COUNT(*) FROM products WHERE type='spare_part' AND $activeProduct"),
            'categories'      => $n('SELECT COUNT(*) FROM categories'),
            'brands'          => $n('SELECT COUNT(*) FROM brands'),
            'services'        => $n('SELECT COUNT(*) FROM services'),
            'featured'        => $n("SELECT COUNT(*) FROM products WHERE featured = 1 AND $activeProduct"),
            'offers'          => $n("SELECT COUNT(*) FROM products WHERE is_offer = 1 AND $activeProduct"),
            'inactive'        => $n("SELECT COUNT(*) FROM products WHERE active = 0 AND $anyProduct"),
            'inquiries_new'   => $n("SELECT COUNT(*) FROM inquiries WHERE status = 'nueva'"),
            'inquiries_total' => $n('SELECT COUNT(*) FROM inquiries'),
        ];

        // Cosas para revisar (cada una enlaza al listado filtrado)
        $review = [
            'no_price' => [
                'label' => 'Productos sin precio',
                'count' => $n("SELECT COUNT(*) FROM products WHERE final_price <= 0 AND $activeProduct"),
                'url'   => admin_url('repuestos?sin_precio=1'),
                'icon'  => 'bi-tag',
            ],
            'no_image' => [
                'label' => 'Productos sin foto',
                'count' => $n("SELECT COUNT(*) FROM products p WHERE $activeProduct
                                AND NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)"),
                'url'   => admin_url('maquinaria?sin_imagen=1'),
                'icon'  => 'bi-image',
            ],
            'no_category' => [
                'label' => 'Productos sin categoría',
                'count' => $n("SELECT COUNT(*) FROM products WHERE category_id IS NULL AND $activeProduct"),
                'url'   => admin_url('maquinaria'),
                'icon'  => 'bi-folder-x',
            ],
        ];

        $this->view('admin/dashboard/index', [
            'pageTitle'  => 'Inicio · Panel',
            'adminTitle' => 'Inicio',
            'robots'     => 'noindex, nofollow',
            'stats'      => $stats,
            'review'     => $review,
            'inquiries'  => (new Inquiry())->latest(6),
        ]);
    }

    /** Página del menú Gestión → Novedades: sólo muestra el archivo. */
    public function news(): void
    {
        $this->view('admin/dashboard/novedades', [
            'pageTitle'  => 'Novedades · Panel',
            'adminTitle' => 'Novedades',
            'robots'     => 'noindex, nofollow',
            'news'       => self::newsHtml(),
        ]);
    }

    /** Contenido del archivo de novedades (HTML plano). '' si no existe. */
    private static function newsHtml(): string
    {
        return is_file(self::NEWS_FILE) ? trim((string) file_get_contents(self::NEWS_FILE)) : '';
    }
}
