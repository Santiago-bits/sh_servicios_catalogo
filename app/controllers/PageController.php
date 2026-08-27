<?php
/**
 * ARCHIVO: app/controllers/PageController.php
 * ---------------------------------------------------------------------
 * Páginas institucionales: nosotros, contacto y financiación.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Brand;
use App\Models\FinancingOption;
use App\Models\Service;
use App\Services\SettingService;
use App\Services\WhatsAppService;
use Core\Controller;
use Core\Database;

class PageController extends Controller
{
    public function about(): void
    {
        $counts = Database::selectOne(
            "SELECT
                (SELECT COUNT(*) FROM products WHERE type = 'machine'    AND active = 1 AND deleted_at IS NULL) AS maquinas,
                (SELECT COUNT(*) FROM products WHERE type = 'spare_part' AND active = 1 AND deleted_at IS NULL) AS repuestos,
                (SELECT COUNT(*) FROM brands WHERE active = 1)                                                  AS marcas,
                (SELECT COUNT(*) FROM services WHERE active = 1)                                                AS servicios"
        );

        $this->view('pages/about', [
            'pageTitle'       => 'Nosotros · ' . SettingService::companyName(),
            'metaDescription' => str_limit((string) SettingService::get('company_description', ''), 155),
            'bodyClass'       => 'page-about',
            'counts'          => $counts ?? [],
            'services'        => (new Service())->activeList(),
            'brands'          => (new Brand())->forHomepage(12),
        ]);
    }

    public function contact(): void
    {
        $this->view('pages/contact', [
            'pageTitle'       => 'Contacto · ' . SettingService::companyName(),
            'metaDescription' => 'Escribinos por WhatsApp, email o teléfono. Atención comercial y service técnico.',
            'bodyClass'       => 'page-contact',
            'whatsappLink'    => WhatsAppService::generalLink(),
        ]);
    }

    public function financing(): void
    {
        $options = (new FinancingOption())->activeFor('both');

        $this->view('pages/financing', [
            'pageTitle'       => 'Financiación · ' . SettingService::companyName(),
            'metaDescription' => 'Planes de financiación para maquinaria y repuestos: contado, anticipo más cuotas y leasing.',
            'bodyClass'       => 'page-financing',
            'options'         => $options,
            'methods'         => (new FinancingOption())->paymentMethods(true),
        ]);
    }
}
