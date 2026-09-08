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

        // Cosas para revisar. Cada fila es de un tipo (maquinaria o repuestos)
        // y su "Ver" lleva al listado de ESE tipo con el filtro aplicado, así
        // sólo se ven los productos que tienen esa falta (y no todo el catálogo).
        $checks = [
            'sin_precio'    => ['sin precio',    'p.final_price <= 0',                                                     'bi-tag'],
            'sin_imagen'    => ['sin foto',      'NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)', 'bi-image'],
            'sin_categoria' => ['sin categoría', 'p.category_id IS NULL',                                                  'bi-folder-x'],
        ];

        $review = [];
        foreach (['machine' => ['Máquinas', 'maquinaria'], 'spare_part' => ['Repuestos', 'repuestos']] as $type => [$label, $base]) {
            foreach ($checks as $key => [$suffix, $cond, $icon]) {
                $count = $n("SELECT COUNT(*) FROM products p WHERE p.type = '$type' AND $activeProduct AND ($cond)");
                if ($count === 0) {
                    continue;
                }
                $review[$type . '_' . $key] = [
                    'label' => $label . ' ' . $suffix,
                    'count' => $count,
                    'url'   => admin_url($base . '?' . $key . '=1'),
                    'icon'  => $icon,
                ];
            }
        }

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
