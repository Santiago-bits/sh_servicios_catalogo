<?php
/**
 * ARCHIVO: app/controllers/admin/InquiryController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Inquiry;
use App\Models\User;
use App\Services\AuditService;
use App\Services\WhatsAppService;
use Core\Request;

class InquiryController extends AdminController
{
    private const STATUSES = ['nueva', 'en_proceso', 'respondida', 'cerrada'];

    public function index(): void
    {
        $filters = [
            'q'      => Request::get('q'),
            'estado' => Request::get('estado'),
            'canal'  => Request::get('canal'),
        ];

        $result = (new Inquiry())->search($filters, max(1, Request::int('pagina', 1)), 25);

        $this->view('admin/inquiries/index', [
            'pageTitle'  => 'Consultas · Panel',
            'adminTitle' => 'Consultas',
            'robots'     => 'noindex, nofollow',
            'result'     => $result,
            'inquiries'  => $result['data'],
            'filters'    => $filters,
            'statuses'   => self::STATUSES,
        ]);
    }

    public function show(string $id): void
    {
        $inquiry = (new Inquiry())->findFull((int) $id);

        if ($inquiry === null) {
            $this->abort(404, 'La consulta no existe.');
        }

        // Al abrirla se marca como "en proceso" si estaba nueva
        if ($inquiry['status'] === 'nueva' && can('inquiries.manage')) {
            (new Inquiry())->updateById((int) $id, ['status' => 'en_proceso']);
            $inquiry['status'] = 'en_proceso';
        }

        $whatsapp = '#';
        if (!empty($inquiry['phone'])) {
            $number = preg_replace('/\D+/', '', (string) $inquiry['phone']) ?? '';
            if ($number !== '') {
                if (strlen($number) <= 11 && !str_starts_with($number, '54')) {
                    $number = '54' . $number;
                }
                $whatsapp = 'https://wa.me/' . $number . '?text=' . rawurlencode(
                    'Hola ' . $inquiry['name'] . ', te escribimos de ' . setting('company_name', 'SH Servicios') .
                    ' por tu consulta' . (!empty($inquiry['product_name']) ? ' sobre ' . $inquiry['product_name'] : '') . '.'
                );
            }
        }

        $this->view('admin/inquiries/show', [
            'pageTitle'    => 'Consulta de ' . $inquiry['name'] . ' · Panel',
            'adminTitle'   => 'Consulta',
            'robots'       => 'noindex, nofollow',
            'inquiry'      => $inquiry,
            'statuses'     => self::STATUSES,
            'users'        => (new User())->operators(),
            'whatsappLink' => $whatsapp,
        ]);
    }

    public function update(string $id): void
    {
        $model   = new Inquiry();
        $inquiry = $model->find((int) $id);

        if ($inquiry === null) {
            $this->abort(404, 'La consulta no existe.');
        }

        $status = (string) Request::post('status', $inquiry['status']);

        $payload = [
            'status'        => in_array($status, self::STATUSES, true) ? $status : $inquiry['status'],
            'assigned_to'   => Request::int('assigned_to') ?: null,
            'internal_note' => mb_substr((string) Request::post('internal_note', ''), 0, 4000) ?: null,
        ];

        if ($payload['status'] === 'respondida' && empty($inquiry['replied_at'])) {
            $payload['replied_at'] = date('Y-m-d H:i:s');
        }

        $model->updateById((int) $id, $payload);

        AuditService::log('update', 'inquiries', 'inquiry', (int) $id, 'Consulta de ' . $inquiry['name'] . ' actualizada');

        $this->success('Consulta actualizada.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model   = new Inquiry();
        $inquiry = $model->find((int) $id);

        if ($inquiry === null) {
            $this->abort(404, 'La consulta no existe.');
        }

        $model->deleteById((int) $id);

        AuditService::log('delete', 'inquiries', 'inquiry', (int) $id, 'Consulta de ' . $inquiry['name'] . ' eliminada');

        $this->success('Consulta eliminada.');
        $this->redirect('admin/consultas');
    }
}
