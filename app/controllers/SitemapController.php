<?php
/**
 * ARCHIVO: app/controllers/SitemapController.php
 * ---------------------------------------------------------------------
 * sitemap.xml generado dinámicamente con todas las URLs públicas.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SettingService;
use Core\Controller;
use Core\Database;

class SitemapController extends Controller
{
    public function index(): void
    {
        $base = rtrim(SettingService::siteUrl(), '/');
        $urls = [];

        // Páginas fijas
        foreach ([
            ''              => ['1.0', 'daily'],
            'maquinaria'    => ['0.9', 'daily'],
            'repuestos'     => ['0.9', 'daily'],
            'servicios'     => ['0.7', 'monthly'],
            'contacto'      => ['0.6', 'monthly'],
        ] as $path => [$priority, $frequency]) {
            $urls[] = ['loc' => $base . ($path === '' ? '/' : '/' . $path), 'priority' => $priority, 'changefreq' => $frequency];
        }

        // Categorías
        $categories = Database::select(
            'SELECT slug, type, updated_at FROM categories WHERE active = 1 AND type <> \'service\''
        );

        foreach ($categories as $category) {
            $prefix = $category['type'] === 'machine' ? 'maquinaria' : 'repuestos';
            $urls[] = [
                'loc'        => $base . '/' . $prefix . '/' . $category['slug'],
                'lastmod'    => date('Y-m-d', strtotime((string) $category['updated_at'])),
                'priority'   => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        // Servicios
        foreach (Database::select('SELECT slug, updated_at FROM services WHERE active = 1') as $service) {
            $urls[] = [
                'loc'        => $base . '/servicios/' . $service['slug'],
                'lastmod'    => date('Y-m-d', strtotime((string) $service['updated_at'])),
                'priority'   => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        // Productos
        $products = Database::select(
            'SELECT p.slug, p.type, p.updated_at, c.slug AS category_slug
               FROM products p LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.active = 1 AND p.deleted_at IS NULL
              ORDER BY p.updated_at DESC LIMIT 5000'
        );

        foreach ($products as $product) {
            $prefix   = $product['type'] === 'machine' ? 'maquinaria' : 'repuestos';
            $category = $product['category_slug'] ?: $prefix;

            $urls[] = [
                'loc'        => $base . '/' . $prefix . '/' . $category . '/' . $product['slug'],
                'lastmod'    => date('Y-m-d', strtotime((string) $product['updated_at'])),
                'priority'   => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . e($url['loc']) . "</loc>\n";
            if (isset($url['lastmod'])) {
                echo '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }
            echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            echo '    <priority>' . $url['priority'] . "</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }
}
