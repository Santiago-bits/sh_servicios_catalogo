<?php
/**
 * ARCHIVO: app/controllers/PageController.php
 * ---------------------------------------------------------------------
 * Páginas institucionales: contacto.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SettingService;
use App\Services\WhatsAppService;
use Core\Controller;

class PageController extends Controller
{
    public function contact(): void
    {
        $this->view('pages/contact', [
            'pageTitle'       => 'Contacto · ' . SettingService::companyName(),
            'metaDescription' => 'Escribinos por WhatsApp, email o teléfono. Atención comercial y service técnico.',
            'bodyClass'       => 'page-contact',
            'whatsappLink'    => WhatsAppService::generalLink(),
        ]);
    }
}
