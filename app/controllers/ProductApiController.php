<?php
/**
 * ARCHIVO: app/controllers/ProductApiController.php
 * ---------------------------------------------------------------------
 * Datos de un producto en JSON para el comparador, los favoritos y el
 * cotizador. Devuelve SIEMPRE la vista pública: sin costos ni ganancias.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Services\PriceService;
use App\Services\WhatsAppService;
use Core\Controller;

class ProductApiController extends Controller
{
    public function show(string $id): void
    {
        $productModel = new Product();
        $product      = $productModel->find((int) $id);

        if ($product === null || (int) $product['active'] !== 1 || $product['deleted_at'] !== null) {
            $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
        }

        $full = $productModel->findFull((int) $id);
        if ($full === null) {
            $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
        }

        $safe = PriceService::publicView($full);

        $this->json([
            'ok'      => true,
            'product' => [
                'id'            => (int) $safe['id'],
                'type'          => $safe['type'],
                'code'          => $safe['code'],
                'name'          => $safe['name'],
                'brand'         => $safe['brand_name'],
                'category'      => $safe['category_name'],
                'model'         => $safe['model'] ?? null,
                'year'          => $safe['year'] ?? null,
                'availability'  => availability_badge((string) $safe['availability']),
                'price'         => PriceService::isPublicPriceVisible($safe) ? PriceService::effectivePrice($safe) : null,
                'price_label'   => PriceService::displayPrice($safe),
                'currency'      => $safe['currency'],
                'image'         => product_image_url($safe, false),
                'url'           => product_url($safe),
                'whatsapp'      => WhatsAppService::productLink($safe),
                'features'      => $productModel->features((int) $id),
            ],
        ]);
    }
}
