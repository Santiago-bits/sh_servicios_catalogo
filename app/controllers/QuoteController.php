<?php
/**
 * ARCHIVO: app/controllers/QuoteController.php
 * ---------------------------------------------------------------------
 * Cotizador público: el visitante arma su pedido (máquinas, repuestos,
 * servicios), elige un plan de financiación y lo envía. Se crea una
 * cotización en estado "borrador" para que el equipo la revise.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\Service;
use App\Services\EmailService;
use App\Services\QuoteService;
use App\Services\SettingService;
use App\Services\WhatsAppService;
use Core\Controller;
use Core\Request;

class QuoteController extends Controller
{
    public function index(): void
    {
        $items  = QuoteService::cartItems();
        $totals = QuoteService::totals($items);

        $this->view('quotes/index', [
            'pageTitle'       => 'Solicitar cotización · ' . SettingService::companyName(),
            'metaDescription' => 'Armá tu cotización con máquinas, repuestos y servicios, y recibila por email o WhatsApp.',
            'bodyClass'       => 'page-quote',
            'robots'          => 'noindex, follow',
            'items'           => $items,
            'totals'          => $totals,
            'services'        => (new Service())->activeList(),
        ]);
    }

    // ----------------------------------------------------------------
    // Carrito (AJAX)
    // ----------------------------------------------------------------

    public function addItem(): void
    {
        $productId = Request::int('product_id');
        $quantity  = Request::float('quantity', 1);

        $result = QuoteService::addToCart($productId, $quantity);

        $this->json([
            'ok'      => $result['ok'],
            'message' => $result['message'],
            'count'   => $result['count'],
        ], $result['ok'] ? 200 : 422);
    }

    public function removeItem(): void
    {
        $count = QuoteService::removeFromCart(Request::int('product_id'));
        $this->json(['ok' => true, 'count' => $count, 'message' => 'Ítem quitado de la cotización.']);
    }

    public function clear(): void
    {
        QuoteService::clearCart();
        $this->json(['ok' => true, 'count' => 0, 'message' => 'Cotización vaciada.']);
    }

    public function cart(): void
    {
        $items  = QuoteService::cartItems();
        $totals = QuoteService::totals($items);

        $this->json([
            'ok'     => true,
            'count'  => count($items),
            'totals' => $totals,
            'items'  => array_map(static fn (array $i): array => [
                'id'          => $i['product_id'],
                'description' => $i['description'],
                'code'        => $i['code'],
                'quantity'    => $i['quantity'],
                'unit_price'  => $i['price_hidden'] ? null : $i['unit_price'],
                'line_total'  => $i['price_hidden'] ? null : $i['line_total'],
                'image'       => product_image_url($i['product']),
                'url'         => product_url($i['product']),
            ], $items),
        ]);
    }

    // ----------------------------------------------------------------
    // Envío
    // ----------------------------------------------------------------

    public function store(): void
    {
        // Honeypot anti-spam: campo oculto que sólo completan los bots
        if (Request::post('website') !== null && Request::post('website') !== '') {
            $this->redirect('cotizador');
        }

        // Límite por IP (cada cotización web también genera una consulta,
        // así que se reutiliza el mismo contador).
        if ((new Inquiry())->tooManyFrom(Request::ip(), 8)) {
            $this->error('Recibimos varias solicitudes desde esta conexión. Probá más tarde o escribinos por WhatsApp.');
            $this->redirect('cotizador');
        }

        $data = $this->validate(Request::all(), [
            'nombre'   => 'required|string|min:3|max:160',
            'email'    => 'required|email|max:160',
            'telefono' => 'required|phone|max:40',
            'empresa'  => 'max:160',
            'cuit'     => 'max:40',
            'mensaje'  => 'max:2000',
        ], [
            'nombre'   => 'nombre',
            'email'    => 'email',
            'telefono' => 'teléfono',
            'empresa'  => 'empresa',
            'cuit'     => 'CUIT',
            'mensaje'  => 'mensaje',
        ]);

        $items = QuoteService::cartItems();

        // Servicios elegidos con los checkboxes
        foreach (Request::array('servicios') as $serviceTitle) {
            if (trim($serviceTitle) !== '') {
                $items[] = [
                    'product_id'  => null,
                    'item_type'   => 'service',
                    'code'        => null,
                    'description' => 'Servicio: ' . mb_substr($serviceTitle, 0, 200),
                    'quantity'    => 1,
                    'unit_price'  => 0,
                    'line_total'  => 0,
                ];
            }
        }

        if ($items === []) {
            $this->error('Agregá al menos un producto o servicio a la cotización.');
            $this->redirect('cotizador');
        }

        $result = QuoteService::create(
            [
                'name'    => $data['nombre'],
                'email'   => $data['email'],
                'phone'   => $data['telefono'],
                'company' => $data['empresa'] ?? null,
                'taxid'   => $data['cuit'] ?? null,
            ],
            $items,
            [
                'status'              => 'borrador',
                'source'              => 'web',
                'notes'               => $data['mensaje'] ?? null,
                'financing_option_id' => null,
            ]
        );

        if (!$result['ok']) {
            $this->error($result['message']);
            $this->redirect('cotizador');
        }

        QuoteService::clearCart();

        // Detalle de lo pedido, para el mensaje de la consulta
        $lines = [];
        foreach ($items as $it) {
            $desc = trim((string) ($it['description'] ?? $it['code'] ?? 'Ítem'));
            $lines[] = '· ' . $desc . ' (x' . (int) ($it['quantity'] ?? 1) . ')';
        }
        $detalle = "Productos / servicios solicitados:\n" . implode("\n", $lines);
        $mensaje = trim(($data['mensaje'] ?? '') . "\n\n" . $detalle);

        // Se registra como consulta para que aparezca en el panel (Consultas)
        (new Inquiry())->create([
            'name'       => $data['nombre'],
            'email'      => $data['email'],
            'phone'      => $data['telefono'] ?? null,
            'company'    => $data['empresa'] ?? null,
            'product_id' => null,
            'subject'    => 'Solicitud de cotización (' . count($items) . ' ítem/s)',
            'message'    => $mensaje,
            'channel'    => 'web',
            'status'     => 'nueva',
            'ip'         => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        // Aviso interno por email (según la configuración de .env)
        EmailService::newInquiry([
            'name'         => $data['nombre'],
            'email'        => $data['email'],
            'phone'        => $data['telefono'],
            'company'      => $data['empresa'] ?? null,
            'product_name' => 'Solicitud de cotización',
            'message'      => $mensaje,
        ]);

        $this->redirect('cotizador/enviada/' . $result['number']);
    }

    public function success(string $number): void
    {
        $quote = (new \App\Models\Quote())->findByNumber($number);

        if ($quote === null) {
            $this->abort(404, 'La cotización solicitada no existe.');
        }

        $this->view('quotes/success', [
            'pageTitle'    => 'Cotización enviada · ' . SettingService::companyName(),
            'bodyClass'    => 'page-quote-success',
            'robots'       => 'noindex, nofollow',
            'quote'        => $quote,
            'whatsappLink' => WhatsAppService::link(
                'Hola, acabo de enviar una solicitud de cotización desde la web. Quedo a la espera.'
            ),
        ]);
    }
}
