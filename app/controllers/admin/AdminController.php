<?php
/**
 * ARCHIVO: app/controllers/admin/AdminController.php
 * ---------------------------------------------------------------------
 * Base de todos los controladores del panel: fija el layout y agrega
 * los datos comunes de la barra lateral.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\AlertService;
use App\Models\Inquiry;
use Core\Controller;

abstract class AdminController extends Controller
{
    protected string $layout = 'admin';

    /** @param array<string,mixed> $data */
    protected function view(string $template, array $data = [], ?int $status = null): void
    {
        $data['pendingInquiries'] = $data['pendingInquiries'] ?? (new Inquiry())->countNew();
        $data['alertCount']       = $data['alertCount'] ?? AlertService::urgentCount();
        $data['adminTitle']       = $data['adminTitle'] ?? 'Panel';

        parent::view($template, $data, $status);
    }
}
