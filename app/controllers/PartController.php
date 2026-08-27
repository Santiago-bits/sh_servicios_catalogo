<?php
/**
 * ARCHIVO: app/controllers/PartController.php
 * ---------------------------------------------------------------------
 * Catálogo público de repuestos. Además del listado clásico incorpora
 * la búsqueda por código OEM y por modelo de máquina compatible, que
 * es el punto fuerte de esta sección.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\FinancingService;
use App\Services\PriceService;
use App\Services\SearchService;
use App\Services\SettingService;
use App\Services\StatsService;
use App\Services\WhatsAppService;
use Core\Controller;
use Core\Request;

class PartController extends Controller
{
    private const SORTS = [
        'destacados'  => 'Destacados',
        'az'          => 'Nombre A-Z',
        'codigo'      => 'Código',
        'precio_asc'  => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
        'nuevos'      => 'Más nuevos',
        'vistos'      => 'Más buscados',
        'stock'       => 'Mayor stock',
    ];

    public function index(?string $category = null): void
    {
        $filters = $this->filters($category);
        $page    = max(1, Request::int('pagina', 1));

        $product = new Product();
        $result  = $product->catalog('spare_part', $filters, $page, SettingService::perPage());

        // Si el término escrito es un modelo de máquina, se ofrecen los
        // repuestos compatibles aunque el nombre no coincida.
        $matchedModel   = null;
        $compatibleList = [];

        if (!empty($filters['q'])) {
            SearchService::logCatalogSearch((string) $filters['q'], 'spare_part', $result['total']);

            $matchedModel = SearchService::matchModel((string) $filters['q']);
            if ($matchedModel !== null) {
                $foundIds       = array_column($result['data'], 'id');
                $compatibleList = array_values(array_filter(
                    $product->partsForModel($matchedModel, 12),
                    static fn (array $p): bool => !in_array($p['id'], $foundIds, true)
                ));
            }
        }

        $categoryModel   = new Category();
        $currentCategory = $category !== null ? $categoryModel->findBySlugAndType($category, 'spare_part') : null;

        if ($category !== null && $currentCategory === null) {
            $this->abort(404, 'La categoría de repuestos solicitada no existe.');
        }

        $this->view('parts/index', [
            'pageTitle'       => $currentCategory !== null
                ? $currentCategory['name'] . ' · Repuestos · ' . SettingService::companyName()
                : 'Repuestos · ' . SettingService::companyName(),
            'metaDescription' => $currentCategory['description']
                ?? 'Buscá repuestos por código interno, código OEM o modelo de máquina. Filtros, compatibilidad y stock en tiempo real.',
            'bodyClass'       => 'page-catalog page-parts',

            'result'          => $result,
            'products'        => $result['data'],
            'filters'         => $filters,
            'currentCategory' => $currentCategory,
            'categories'      => $categoryModel->ofType('spare_part'),
            'brands'          => $product->availableBrands('spare_part'),
            'priceRange'      => $product->priceRange('spare_part'),
            'sorts'           => self::SORTS,
            'viewMode'        => Request::get('vista') === 'grid' ? 'grid' : 'lista',
            'matchedModel'    => $matchedModel,
            'compatibleList'  => $compatibleList,
            'type'            => 'spare_part',
        ]);
    }

    public function ajaxIndex(): void
    {
        $filters = $this->filters(Request::get('categoria'));
        $page    = max(1, Request::int('pagina', 1));

        $result = (new Product())->catalog('spare_part', $filters, $page, SettingService::perPage());

        $this->json([
            'ok'   => true,
            'html' => $this->fragment('partials/product-grid', [
                'products' => $result['data'],
                'viewMode' => Request::get('vista') === 'grid' ? 'grid' : 'lista',
                'type'     => 'spare_part',
            ]),
            'pagination' => $this->fragment('partials/pagination', ['result' => $result]),
            'total'      => $result['total'],
            'page'       => $result['page'],
            'last_page'  => $result['last_page'],
        ]);
    }

    public function show(string $category, string $slug): void
    {
        $productModel = new Product();
        $product      = $productModel->findBySlug($slug, 'spare_part');

        if ($product === null) {
            $this->abort(404, 'El repuesto que buscás no está disponible.');
        }

        StatsService::trackView((int) $product['id']);

        $price = PriceService::effectivePrice($product);

        $this->view('parts/show', [
            'pageTitle'       => ($product['meta_title'] ?: $product['name']) . ' · Repuestos · ' . SettingService::companyName(),
            'metaDescription' => $product['meta_description'] ?: str_limit((string) $product['short_description'], 155),
            'ogImage'         => $product['image'] ? upload_url((string) $product['image']) : null,
            'canonical'       => part_url($product),
            'bodyClass'       => 'page-product page-part',

            'product'           => $product,
            'images'            => $productModel->images((int) $product['id']),
            'featureGroups'     => $productModel->features((int) $product['id']),
            'tags'              => $productModel->tags((int) $product['id']),
            'documents'         => $productModel->documents((int) $product['id']),
            'codes'             => $productModel->codes((int) $product['id']),
            'compatibility'     => $productModel->compatibilityList((int) $product['id']),
            'compatibleMachines'=> $productModel->compatibleMachines((int) $product['id'], 8),
            'similar'           => $productModel->similar($product, 4),
            'financingPlans'    => PriceService::isPublicPriceVisible($product) && $price > 100000
                                   ? FinancingService::plansFor($price, 'spare_part', (string) $product['currency'])
                                   : [],
            'whatsappLink'      => WhatsAppService::partLink($product),
            'type'              => 'spare_part',
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(?string $category): array
    {
        return [
            'categoria'  => $category ?? Request::get('categoria'),
            'marca'      => Request::array('marca') ?: Request::get('marca'),
            'q'          => Request::get('q'),
            'precio_min' => Request::get('precio_min'),
            'precio_max' => Request::get('precio_max'),
            'etiqueta'   => Request::get('etiqueta'),
            'con_stock'  => Request::get('con_stock'),
            'destacados' => Request::get('destacados'),
            'ofertas'    => Request::get('ofertas'),
            'orden'      => Request::get('orden', 'destacados'),
        ];
    }
}
