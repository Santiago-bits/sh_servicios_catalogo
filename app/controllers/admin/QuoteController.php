<?php
/**
 * ARCHIVO: app/controllers/admin/QuoteController.php
 * ---------------------------------------------------------------------
 * Cotizaciones desde el panel: alta manual, edición, cambio de estado,
 * PDF y envío por email / WhatsApp.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\FinancingOption;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\FinancingService;
use App\Services\PdfService;
use App\Services\QuoteService;
use App\Services\SettingService;
use App\Services\WhatsAppService;
use Core\Auth;
use Core\Database;
use Core\Request;

class QuoteController extends AdminController
{
    private const STATUSES = ['borrador', 'enviada', 'aceptada', 'rechazada', 'vencida'];

    public function index(): void
    {
        $model = new Quote();
        $model->expireOverdue();

        $filters = [
            'q'       => Request::get('q'),
            'estado'  => Request::get('estado'),
            'desde'   => Request::get('desde'),
            'hasta'   => Request::get('hasta'),
            'usuario' => Request::get('usuario'),
        ];

        $result = $model->search($filters, max(1, Request::int('pagina', 1)), 25);

        $this->view('admin/quotes/index', [
            'pageTitle'  => 'Cotizaciones · Panel',
            'adminTitle' => 'Cotizaciones',
            'robots'     => 'noindex, nofollow',
            'result'     => $result,
            'quotes'     => $result['data'],
            'filters'    => $filters,
            'statuses'   => self::STATUSES,
            'stats'      => $model->stats(),
            'users'      => (new User())->operators(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm(null);
    }

    public function edit(string $id): void
    {
        $quote = (new Quote())->findFull((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        $this->renderForm($quote);
    }

    private function renderForm(?array $quote): void
    {
        $isEdit = $quote !== null;

        $this->view('admin/quotes/form', [
            'pageTitle'  => ($isEdit ? 'Editar cotización ' . $quote['number'] : 'Nueva cotización') . ' · Panel',
            'adminTitle' => $isEdit ? 'Editar cotización ' . $quote['number'] : 'Nueva cotización',
            'robots'     => 'noindex, nofollow',
            'quote'      => $quote,
            'isEdit'     => $isEdit,
            'items'      => $isEdit ? $quote['items'] : [],
            'financing'  => (new FinancingOption())->allWithMethod(),
            'statuses'   => self::STATUSES,
            'conditions' => (string) SettingService::get('quote_conditions', ''),
            'validity'   => SettingService::int('quote_validity_days', 15),
            'nextNumber' => $isEdit ? $quote['number'] : (new Quote())->nextNumber(
                (string) SettingService::get('quote_prefix', 'COT-'),
                SettingService::int('quote_padding', 6)
            ),
            'catalog'    => Database::select(
                'SELECT p.id, p.code, p.name, p.type, p.final_price, p.offer_price, p.is_offer, p.currency
                   FROM products p
                  WHERE p.active = 1 AND p.deleted_at IS NULL
                  ORDER BY p.type ASC, p.name ASC'
            ),
        ]);
    }

    public function store(): void
    {
        $data  = $this->validateQuote();
        $items = QuoteService::normalizeItems($this->rawItems(), true);

        if ($items === []) {
            $this->error('Agregá al menos un ítem a la cotización.');
            $this->back();
        }

        $result = QuoteService::create(
            [
                'name'    => $data['customer_name'],
                'company' => $data['customer_company'] ?? null,
                'email'   => $data['customer_email'] ?? null,
                'phone'   => $data['customer_phone'] ?? null,
                'taxid'   => $data['customer_taxid'] ?? null,
                'address' => $data['customer_address'] ?? null,
            ],
            $items,
            [
                'status'              => in_array($data['status'] ?? '', self::STATUSES, true) ? $data['status'] : 'borrador',
                'source'              => 'admin',
                'currency'            => Request::post('currency') === 'USD' ? 'USD' : 'ARS',
                'discount_percent'    => Request::float('discount_percent'),
                'discount_amount'     => Request::float('discount_amount'),
                'shipping_cost'       => Request::float('shipping_cost'),
                'other_costs'         => Request::float('other_costs'),
                'financing_option_id' => Request::int('financing_option_id') ?: null,
                'notes'               => $data['notes'] ?? null,
                'conditions'          => $data['conditions'] ?? null,
                'valid_until'         => $data['valid_until'] ?? null,
            ]
        );

        if (!$result['ok']) {
            $this->error($result['message']);
            $this->back();
        }

        $this->success($result['message']);
        $this->redirect('admin/cotizaciones/' . $result['id']);
    }

    public function update(string $id): void
    {
        $model = new Quote();
        $quote = $model->findFull((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        $data  = $this->validateQuote();
        $items = QuoteService::normalizeItems($this->rawItems(), true);

        if ($items === []) {
            $this->error('La cotización debe tener al menos un ítem.');
            $this->back();
        }

        Database::transaction(static function () use ($model, $quote, $data, $items, $id): void {
            $options = [
                'discount_percent' => Request::float('discount_percent'),
                'discount_amount'  => Request::float('discount_amount'),
                'shipping_cost'    => Request::float('shipping_cost'),
                'other_costs'      => Request::float('other_costs'),
            ];

            $totals = QuoteService::totals($items, $options);

            $financingId = Request::int('financing_option_id') ?: null;
            $financing   = $financingId !== null
                ? FinancingService::calculateWithOption($totals['total'], $financingId, (string) $quote['currency'])
                : null;

            if ($financing !== null) {
                $totals['interest_amount'] = $financing['interest_amount'];
                $totals['total']           = $financing['total'];
            }

            $payload = array_merge($totals, [
                'customer_name'       => $data['customer_name'],
                'customer_company'    => $data['customer_company'] ?? null,
                'customer_email'      => $data['customer_email'] ?? null,
                'customer_phone'      => $data['customer_phone'] ?? null,
                'customer_taxid'      => $data['customer_taxid'] ?? null,
                'customer_address'    => $data['customer_address'] ?? null,
                'status'              => in_array($data['status'] ?? '', self::STATUSES, true) ? $data['status'] : $quote['status'],
                'currency'            => Request::post('currency') === 'USD' ? 'USD' : 'ARS',
                'financing_option_id' => $financingId,
                'down_payment'        => $financing['down_payment'] ?? 0,
                'installments'        => $financing['installments'] ?? 0,
                'installment_amount'  => $financing['installment_amount'] ?? 0,
                'notes'               => $data['notes'] ?? null,
                'conditions'          => $data['conditions'] ?? null,
                'valid_until'         => $data['valid_until'] ?? null,
            ]);

            $model->updateById((int) $id, $payload);
            $model->replaceItems((int) $id, $items);

            if ($financing !== null && $financing['installments'] > 0) {
                $model->replacePayments((int) $id, array_map(
                    static fn (array $p): array => ['concept' => $p['concept'], 'amount' => $p['amount'], 'due_date' => $p['due_date']],
                    FinancingService::paymentSchedule($financing)
                ));
            } else {
                $model->replacePayments((int) $id, []);
            }

            AuditService::log('update', 'quotes', 'quote', (int) $id, 'Cotización ' . $quote['number'] . ' modificada');
        });

        $this->success('Cotización actualizada.');
        $this->redirect('admin/cotizaciones/' . $id);
    }

    public function show(string $id): void
    {
        $quote = (new Quote())->findFull((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        $this->view('admin/quotes/show', [
            'pageTitle'    => 'Cotización ' . $quote['number'] . ' · Panel',
            'adminTitle'   => 'Cotización ' . $quote['number'],
            'robots'       => 'noindex, nofollow',
            'quote'        => $quote,
            'statuses'     => self::STATUSES,
            'whatsappLink' => WhatsAppService::quoteLink($quote),
        ]);
    }

    public function changeStatus(string $id): void
    {
        $model = new Quote();
        $quote = $model->find((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        $status = (string) Request::post('status', '');

        if (!in_array($status, self::STATUSES, true)) {
            $this->error('Estado inválido.');
            $this->back();
        }

        $payload = ['status' => $status];

        if ($status === 'enviada' && empty($quote['sent_at'])) {
            $payload['sent_at'] = date('Y-m-d H:i:s');
        }
        if (in_array($status, ['aceptada', 'rechazada'], true)) {
            $payload['responded_at'] = date('Y-m-d H:i:s');
        }

        $model->updateById((int) $id, $payload);

        AuditService::log(
            'update',
            'quotes',
            'quote',
            (int) $id,
            'Cotización ' . $quote['number'] . ': estado ' . $quote['status'] . ' → ' . $status
        );

        $this->success('Estado actualizado a "' . quote_status_badge($status)['label'] . '".');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model = new Quote();
        $quote = $model->find((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        $model->deleteById((int) $id);

        AuditService::log('delete', 'quotes', 'quote', (int) $id, 'Cotización ' . $quote['number'] . ' eliminada');

        $this->success('Cotización eliminada.');
        $this->redirect('admin/cotizaciones');
    }

    // ----------------------------------------------------------------
    // PDF y envío
    // ----------------------------------------------------------------

    public function pdf(string $id): void
    {
        $quote = (new Quote())->findFull((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        AuditService::log('export', 'quotes', 'quote', (int) $id, 'PDF generado de ' . $quote['number']);

        $pdf      = PdfService::quote($quote);
        $download = Request::get('descargar') === '1';

        $pdf->stream('Cotizacion-' . $quote['number'] . '.pdf', $download);
    }

    public function sendEmail(string $id): void
    {
        $model = new Quote();
        $quote = $model->findFull((int) $id);

        if ($quote === null) {
            $this->abort(404, 'La cotización no existe.');
        }

        if (empty($quote['customer_email'])) {
            $this->error('La cotización no tiene email del cliente.');
            $this->back();
        }

        // Se guarda el PDF temporalmente para adjuntarlo
        $path = STORAGE_PATH . '/pdf/Cotizacion-' . $quote['number'] . '.pdf';

        if (!is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }

        PdfService::quote($quote)->save($path);

        $result = EmailService::quote($quote, $path);

        if ($result['ok']) {
            $model->updateById((int) $id, [
                'status'  => $quote['status'] === 'borrador' ? 'enviada' : $quote['status'],
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('email', 'quotes', 'quote', (int) $id, 'Cotización ' . $quote['number'] . ' enviada a ' . $quote['customer_email']);

            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }

        @unlink($path);

        $this->back();
    }

    // ----------------------------------------------------------------
    // Auxiliares
    // ----------------------------------------------------------------

    /** @return array<string,mixed> */
    private function validateQuote(): array
    {
        return $this->validate(Request::all(), [
            'customer_name'    => 'required|string|min:3|max:160',
            'customer_company' => 'max:160',
            'customer_email'   => 'email|max:160',
            'customer_phone'   => 'max:40',
            'customer_taxid'   => 'max:40',
            'customer_address' => 'max:255',
            'status'           => 'in:' . implode(',', self::STATUSES),
            'valid_until'      => 'date',
            'notes'            => 'max:4000',
            'conditions'       => 'max:4000',
        ], [
            'customer_name'    => 'cliente',
            'customer_email'   => 'email',
            'customer_phone'   => 'teléfono',
            'valid_until'      => 'validez',
        ]);
    }

    /** Ítems tal como llegan del formulario dinámico. @return array<int,array<string,mixed>> */
    private function rawItems(): array
    {
        $items = $_POST['items'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn ($item): bool => is_array($item)));
    }
}
