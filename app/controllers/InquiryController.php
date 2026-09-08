<?php
/**
 * ARCHIVO: app/controllers/InquiryController.php
 * ---------------------------------------------------------------------
 * Alta de consultas desde el formulario de contacto y desde el botón
 * "Consultar" de cada producto.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\Product;
use App\Services\EmailService;
use Core\Controller;
use Core\Request;

class InquiryController extends Controller
{
    public function store(): void
    {
        $result = $this->save(Request::all());

        if (!$result['ok']) {
            $this->error($result['message']);
            $this->back();
        }

        $this->success('¡Gracias! Recibimos tu consulta y te respondemos a la brevedad.');
        $this->redirect('contacto?enviado=1');
    }

    /** Alta desde el modal de la ficha de producto (AJAX). */
    public function quickStore(): void
    {
        $result = $this->save(Request::all());

        $this->json([
            'ok'      => $result['ok'],
            'message' => $result['message'],
            'errors'  => $result['errors'] ?? [],
        ], $result['ok'] ? 200 : 422);
    }

    /**
     * @param array<string,mixed> $input
     * @return array{ok:bool,message:string,errors?:array<string,string>}
     */
    private function save(array $input): array
    {
        // Honeypot: si viene completo, es un bot
        if (!empty($input['website'])) {
            return ['ok' => true, 'message' => 'Consulta recibida.'];
        }

        $validator = new \Core\Validator($input, [
            'nombre'   => 'required|string|min:3|max:160',
            'email'    => 'required|email|max:160',
            'telefono' => 'max:40',
            'empresa'  => 'max:160',
            'asunto'   => 'max:200',
            'mensaje'  => 'required|string|min:10|max:3000',
        ], [
            'nombre'   => 'nombre',
            'email'    => 'email',
            'telefono' => 'teléfono',
            'empresa'  => 'empresa',
            'asunto'   => 'asunto',
            'mensaje'  => 'mensaje',
        ]);

        if ($validator->fails()) {
            return ['ok' => false, 'message' => 'Revisá los datos del formulario.', 'errors' => $validator->errors()];
        }

        $inquiryModel = new Inquiry();

        // Anti-spam progresivo: hay que esperar entre una consulta y otra,
        // y la espera crece si se insiste (5 min → 15 min → 30 min → 1 h).
        $cooldown = $inquiryModel->cooldownRemaining(Request::ip());
        if ($cooldown > 0) {
            $minutes = (int) ceil($cooldown / 60);
            return ['ok' => false, 'message' => 'Ya nos enviaste una consulta hace poco. Podés mandar otra en '
                . $minutes . ' minuto(s), o escribinos por WhatsApp.'];
        }

        $data      = $validator->validated();
        $productId = (int) ($input['producto_id'] ?? 0);

        $productName = null;
        if ($productId > 0) {
            $product = (new Product())->find($productId);
            if ($product === null) {
                $productId = 0;
            } else {
                $productName = (string) $product['name'];
                (new Product())->incrementCounter($productId, 'inquiries_count');
            }
        }

        $inquiryModel->create([
            'name'       => $data['nombre'],
            'email'      => $data['email'],
            'phone'      => $data['telefono'] ?? null,
            'company'    => $data['empresa'] ?? null,
            'product_id' => $productId ?: null,
            'subject'    => $data['asunto'] ?? ($productName !== null ? 'Consulta por ' . $productName : 'Consulta general'),
            'message'    => $data['mensaje'],
            'channel'    => 'web',
            'status'     => 'nueva',
            'ip'         => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        EmailService::newInquiry([
            'name'         => $data['nombre'],
            'email'        => $data['email'],
            'phone'        => $data['telefono'] ?? null,
            'company'      => $data['empresa'] ?? null,
            'product_name' => $productName,
            'message'      => $data['mensaje'],
        ]);

        return ['ok' => true, 'message' => '¡Gracias! Recibimos tu consulta y te respondemos a la brevedad.'];
    }
}
