<?php
/**
 * ARCHIVO: app/controllers/ServiceController.php
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Service;
use App\Services\SettingService;
use Core\Controller;

class ServiceController extends Controller
{
    public function index(): void
    {
        $this->view('services/index', [
            'pageTitle'       => 'Servicios · ' . SettingService::companyName(),
            'metaDescription' => 'Venta, alquiler, mantenimiento, service, reparación, repuestos, transporte, asesoramiento, inspección y capacitación.',
            'bodyClass'       => 'page-services',
            'services'        => (new Service())->activeList(),
        ]);
    }

    public function show(string $slug): void
    {
        $serviceModel = new Service();
        $service      = $serviceModel->findBySlug($slug);

        if ($service === null) {
            $this->abort(404, 'El servicio solicitado no existe.');
        }

        $this->view('services/show', [
            'pageTitle'       => $service['title'] . ' · ' . SettingService::companyName(),
            'metaDescription' => str_limit((string) $service['short_description'], 155),
            'bodyClass'       => 'page-service',
            'service'         => $service,
            'others'          => array_values(array_filter(
                $serviceModel->activeList(),
                static fn (array $s): bool => (int) $s['id'] !== (int) $service['id']
            )),
        ]);
    }
}
