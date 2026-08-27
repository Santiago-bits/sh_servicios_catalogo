<?php
/**
 * ARCHIVO: app/controllers/HomeController.php
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Services\SettingService;
use Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $product  = new Product();
        $category = new Category();

        $this->view('home/index', [
            'pageTitle'        => (string) SettingService::get('seo_title', SettingService::companyName()),
            'metaDescription'  => (string) SettingService::get('seo_description', ''),
            'bodyClass'        => 'page-home',

            'machineCategories'=> $category->featuredWithProducts('machine', 8),
            'partCategories'   => $category->featuredWithProducts('spare_part', 10),

            'featuredMachines' => $product->featured('machine', 6),
            'featuredParts'    => $product->featured('spare_part', 6),
            'latestMachines'   => $product->latest('machine', 4),

            'services'         => (new Service())->activeList(6),
            'brands'           => (new Brand())->forHomepage(), // todas las activas
        ]);
    }
}
