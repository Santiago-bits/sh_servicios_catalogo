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
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\SettingService;
use Core\Auth;
use Core\Database;
use Core\Request;

class DashboardController extends AdminController
{
    /** Clave del ajuste donde vive el HTML de "Novedades". */
    private const NEWS_KEY = 'dashboard_news';

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
            'pageTitle'   => 'Inicio · Panel',
            'adminTitle'  => 'Inicio',
            'robots'      => 'noindex, nofollow',
            'stats'       => $stats,
            'review'      => $review,
            'inquiries'   => (new Inquiry())->latest(6),
            'news'        => (string) SettingService::get(self::NEWS_KEY, ''),
            'canEditNews' => Auth::can('settings.manage'),
        ]);
    }

    /**
     * Página dedicada (menú Gestión → Novedades): editor del HTML +
     * vista previa de cómo se ve en el inicio.
     */
    public function news(): void
    {
        $this->view('admin/dashboard/novedades', [
            'pageTitle'  => 'Novedades · Panel',
            'adminTitle' => 'Novedades',
            'robots'     => 'noindex, nofollow',
            'news'       => (string) SettingService::get(self::NEWS_KEY, ''),
        ]);
    }

    /**
     * Guarda el HTML de "Novedades" (notas de las actualizaciones que ve
     * el cliente en el inicio del panel). Se escribe en HTML directo y se
     * pasa por clean_html() para no dejar entrar scripts ni estilos raros.
     */
    public function updateNews(): void
    {
        if (!Auth::can('settings.manage')) {
            $this->abort(403, 'No tenés permiso para editar las novedades.');
        }

        $html = clean_html((string) Request::post('news', ''));

        if (mb_strlen($html) > 40000) {
            $html = mb_substr($html, 0, 40000);
        }

        (new Setting())->upsert(self::NEWS_KEY, $html, 'sistema', 'textarea', 'Novedades del panel');
        SettingService::flush();

        AuditService::log('settings', 'settings', null, null, 'Novedades del panel actualizadas', [self::NEWS_KEY]);

        $this->success('Novedades actualizadas.');

        $return = Request::post('_return') === 'novedades' ? 'admin/novedades' : 'admin';
        $this->redirect($return);
    }
}
