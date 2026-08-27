<?php
/**
 * ARCHIVO: app/controllers/FavoriteController.php
 * ---------------------------------------------------------------------
 * Favoritos del visitante. Hoy viven en localStorage; la tabla
 * `favorites` ya existe para cuando se agreguen cuentas de cliente,
 * así que acá se sincroniza lo que el navegador tiene guardado.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Services\PriceService;
use App\Services\SettingService;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Session;

class FavoriteController extends Controller
{
    public function index(): void
    {
        $ids = array_filter(array_map('intval', explode(',', (string) Request::get('ids', ''))));

        $products = $ids === [] ? [] : (new Product())->findMany(array_slice($ids, 0, 60));

        $this->view('pages/favorites', [
            'pageTitle'       => 'Mis favoritos · ' . SettingService::companyName(),
            'metaDescription' => 'Guardá máquinas y repuestos para consultarlos después.',
            'bodyClass'       => 'page-favorites',
            'robots'          => 'noindex, nofollow',
            'products'        => $products,
        ]);
    }

    /**
     * Guarda en la base los favoritos del visitante (analítica y base
     * para el futuro e-commerce). El navegador manda su lista completa.
     */
    public function sync(): void
    {
        $payload = Request::json() ?? [];
        $ids     = array_slice(array_filter(array_map('intval', $payload['ids'] ?? [])), 0, 60);
        $token   = Session::visitorToken();

        try {
            Database::delete('favorites', 'session_token = :token AND user_id IS NULL', ['token' => $token]);

            foreach ($ids as $id) {
                Database::execute(
                    'INSERT IGNORE INTO favorites (product_id, user_id, session_token) VALUES (:p, NULL, :t)',
                    ['p' => $id, 't' => $token]
                );
            }
        } catch (\Throwable $e) {
            error_log('[FAV] ' . $e->getMessage());
        }

        $products = $ids === [] ? [] : (new Product())->findMany($ids);

        $this->json([
            'ok'       => true,
            'count'    => count($products),
            'products' => array_map(static function (array $p): array {
                $p = PriceService::publicView($p);
                return [
                    'id'    => (int) $p['id'],
                    'name'  => $p['name'],
                    'code'  => $p['code'],
                    'url'   => product_url($p),
                    'image' => upload_url($p['thumb'] ?? $p['image']),
                    'price' => PriceService::displayPrice($p),
                ];
            }, $products),
        ]);
    }
}
