<?php
/**
 * ARCHIVO: app/controllers/ErrorController.php
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SettingService;
use Core\Controller;
use Core\Request;

class ErrorController extends Controller
{
    public function notFound(): void
    {
        http_response_code(404);

        // Dentro del panel se muestra el error con el layout del panel
        if (str_starts_with(Request::uri(), '/admin')) {
            $this->layout = 'admin-bare';
        }

        $this->view('errors/404', [
            'pageTitle' => 'Página no encontrada · ' . SettingService::companyName(),
            'bodyClass' => 'page-error',
            'robots'    => 'noindex, nofollow',
            'code'      => 404,
            'message'   => 'La página que buscás no existe o fue movida.',
        ]);
    }
}
