<?php
/**
 * ARCHIVO: app/controllers/MachineController.php
 * ---------------------------------------------------------------------
 * Catálogo público de maquinaria: listado con filtros y ficha completa.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Machine;
use App\Models\Product;
use App\Services\PriceService;
use App\Services\SearchService;
use App\Services\SettingService;
use App\Services\StatsService;
use App\Services\WhatsAppService;
use Core\Controller;
use Core\Request;

class MachineController extends Controller
{
    private const SORTS = [
        'destacados'  => 'Destacados',
        'precio_asc'  => 'Precio: menor a mayor',
        'precio_desc' => 'Precio: mayor a menor',
        'nuevos'      => 'Más nuevos',
        'vistos'      => 'Más vistos',
        'az'          => 'Nombre A-Z',
    ];

    public function index(?string $category = null): void
    {
        $filters = $this->filters($category);
        $page    = max(1, Request::int('pagina', 1));
        $perPage = SettingService::perPage();

        $product = new Product();
        $result  = $product->catalog('machine', $filters, $page, $perPage);

        if (!empty($filters['q'])) {
            SearchService::logCatalogSearch((string) $filters['q'], 'machine', $result['total']);
        }

        $categoryModel = new Category();
        $currentCategory = $category !== null ? $categoryModel->findBySlugAndType($category, 'machine') : null;

        if ($category !== null && $currentCategory === null) {
            $this->abort(404, 'La categoría solicitada no existe.');
        }

        $machineModel = new Machine();

        $this->view('machines/index', [
            'pageTitle'       => $currentCategory !== null
                ? $currentCategory['name'] . ' · ' . SettingService::companyName()
                : 'Maquinaria · ' . SettingService::companyName(),
            'metaDescription' => $currentCategory['description']
                ?? 'Autoelevadores, apiladores, zorras eléctricas, plataformas y maquinaria industrial nueva y usada.',
            'bodyClass'       => 'page-catalog',

            'result'          => $result,
            'products'        => $result['data'],
            'filters'         => $filters,
            'currentCategory' => $currentCategory,
            'categories'      => $categoryModel->ofType('machine'),
            'brands'          => $product->availableBrands('machine'),
            'priceRange'      => $product->priceRange('machine'),
            'yearRange'       => $machineModel->yearRange(),
            'locations'       => $machineModel->distinctLocations(),
            'sorts'           => self::SORTS,
            'viewMode'        => Request::get('vista') === 'lista' ? 'lista' : 'grid',
            'type'            => 'machine',
        ]);
    }

    /** Listado en JSON para los filtros AJAX. */
    public function ajaxIndex(): void
    {
        $filters = $this->filters(Request::get('categoria'));
        $page    = max(1, Request::int('pagina', 1));

        $result = (new Product())->catalog('machine', $filters, $page, SettingService::perPage());

        $this->json([
            'ok'    => true,
            'html'  => $this->fragment('partials/product-grid', [
                'products' => $result['data'],
                'viewMode' => Request::get('vista') === 'lista' ? 'lista' : 'grid',
                'type'     => 'machine',
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
        $product      = $productModel->findBySlug($slug, 'machine');

        if ($product === null) {
            $this->abort(404, 'La máquina que buscás no está disponible.');
        }

        StatsService::trackView((int) $product['id']);

        $this->view('machines/show', [
            'pageTitle'       => ($product['meta_title'] ?: $product['name']) . ' · ' . SettingService::companyName(),
            'metaDescription' => $product['meta_description'] ?: str_limit((string) $product['short_description'], 155),
            'ogImage'         => $product['image'] ? upload_url((string) $product['image']) : null,
            'canonical'       => machine_url($product),
            'bodyClass'       => 'page-product',

            'product'         => $product,
            'images'          => $productModel->images((int) $product['id']),
            'featureGroups'   => $productModel->features((int) $product['id']),
            'tags'            => $productModel->tags((int) $product['id']),
            'documents'       => $productModel->documents((int) $product['id']),
            'videos'          => $productModel->videos((int) $product['id']),
            'compatibleParts' => $productModel->compatibleParts((int) $product['id'], 8),
            'similar'         => $productModel->similar($product, 4),
            'whatsappLink'    => WhatsAppService::machineLink($product),
            'type'            => 'machine',
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(?string $category): array
    {
        return [
            'categoria'     => $category ?? Request::get('categoria'),
            'marca'         => Request::array('marca') ?: Request::get('marca'),
            'q'             => Request::get('q'),
            'modelo'        => Request::get('modelo'),
            'precio_min'    => Request::get('precio_min'),
            'precio_max'    => Request::get('precio_max'),
            'anio_min'      => Request::get('anio_min'),
            'anio_max'      => Request::get('anio_max'),
            'capacidad_min' => Request::get('capacidad_min'),
            'capacidad_max' => Request::get('capacidad_max'),
            'altura_min'    => Request::get('altura_min'),
            'altura_max'    => Request::get('altura_max'),
            'combustible'   => Request::array('combustible') ?: Request::get('combustible'),
            'estado'        => Request::get('estado'),
            'condicion'     => Request::get('condicion'),
            'ubicacion'     => Request::get('ubicacion'),
            'etiqueta'      => Request::get('etiqueta'),
            'destacados'    => Request::get('destacados'),
            'ofertas'       => Request::get('ofertas'),
            'orden'         => Request::get('orden', 'destacados'),
        ];
    }
}
