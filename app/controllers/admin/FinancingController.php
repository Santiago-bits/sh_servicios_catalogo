<?php
/**
 * ARCHIVO: app/controllers/admin/FinancingController.php
 * ---------------------------------------------------------------------
 * Configuración de métodos de pago y planes de financiación.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\FinancingOption;
use App\Services\AuditService;
use App\Services\FinancingService;
use Core\Database;
use Core\Request;

class FinancingController extends AdminController
{
    public function index(): void
    {
        $model = new FinancingOption();

        // Vista previa con un importe de ejemplo
        $sample  = Request::float('ejemplo', 20000000);
        $preview = FinancingService::plansFor($sample, 'machine');

        $this->view('admin/financing/index', [
            'pageTitle'  => 'Financiación · Panel',
            'adminTitle' => 'Financiación',
            'robots'     => 'noindex, nofollow',
            'options'    => $model->allWithMethod(),
            'methods'    => $model->paymentMethods(),
            'preview'    => $preview,
            'sample'     => $sample,
            'appliesTo'  => ['both' => 'Maquinaria y repuestos', 'machine' => 'Sólo maquinaria', 'spare_part' => 'Sólo repuestos'],
        ]);
    }

    // ----------------------------------------------------------------
    // Planes
    // ----------------------------------------------------------------

    public function store(): void
    {
        $data = $this->validateOption();
        $id   = (new FinancingOption())->create($data);

        AuditService::log('create', 'financing', 'financing_option', $id, 'Plan creado: ' . $data['name']);

        $this->success('Plan de financiación creado.');
        $this->back();
    }

    public function update(string $id): void
    {
        $model  = new FinancingOption();
        $option = $model->find((int) $id);

        if ($option === null) {
            $this->abort(404, 'El plan no existe.');
        }

        $data = $this->validateOption();
        $model->updateById((int) $id, $data);

        AuditService::logChanges('financing', 'financing_option', (int) $id, $option, $data, 'Plan editado: ' . $data['name']);

        $this->success('Plan actualizado.');
        $this->back();
    }

    public function destroy(string $id): void
    {
        $model  = new FinancingOption();
        $option = $model->find((int) $id);

        if ($option === null) {
            $this->abort(404, 'El plan no existe.');
        }

        $inUse = (int) Database::scalar(
            'SELECT COUNT(*) FROM quotes WHERE financing_option_id = :id',
            ['id' => (int) $id]
        ) > 0;

        if ($inUse) {
            $model->updateById((int) $id, ['active' => 0]);
            $this->success('El plan se desactivó (está usado en cotizaciones, por eso no se elimina).');
            $this->back();
        }

        $model->deleteById((int) $id);

        AuditService::log('delete', 'financing', 'financing_option', (int) $id, 'Plan eliminado: ' . $option['name']);

        $this->success('Plan eliminado.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateOption(): array
    {
        $data = $this->validate(Request::all(), [
            'name'                 => 'required|string|min:2|max:120',
            'description'          => 'max:255',
            'payment_method_id'    => 'integer',
            'down_payment_percent' => 'numeric|between:0,100',
            'installments'         => 'required|integer|between:1,120',
            'interest_percent'     => 'numeric|between:0,500',
            'interest_type'        => 'required|in:total,mensual',
            'applies_to'           => 'required|in:machine,spare_part,both',
            'sort_order'           => 'integer',
        ], [
            'name'                 => 'nombre',
            'installments'         => 'cuotas',
            'down_payment_percent' => 'anticipo',
            'interest_percent'     => 'interés',
            'interest_type'        => 'tipo de interés',
            'applies_to'           => 'se aplica a',
        ]);

        $min = Request::float('min_amount');
        $max = Request::float('max_amount');

        return [
            'name'                 => $data['name'],
            'description'          => $data['description'] ?? null,
            'payment_method_id'    => !empty($data['payment_method_id']) ? (int) $data['payment_method_id'] : null,
            'down_payment_percent' => (float) ($data['down_payment_percent'] ?? 0),
            'installments'         => (int) $data['installments'],
            'interest_percent'     => (float) ($data['interest_percent'] ?? 0),
            'interest_type'        => $data['interest_type'],
            'min_amount'           => $min > 0 ? $min : null,
            'max_amount'           => $max > 0 ? $max : null,
            'currency'             => Request::post('currency') === 'USD' ? 'USD' : 'ARS',
            'applies_to'           => $data['applies_to'],
            'featured'             => Request::flag('featured'),
            'sort_order'           => (int) ($data['sort_order'] ?? 0),
            'active'               => Request::flag('active', true),
        ];
    }

    // ----------------------------------------------------------------
    // Métodos de pago
    // ----------------------------------------------------------------

    public function storeMethod(): void
    {
        $data = $this->validateMethod();

        $id = Database::insert('payment_methods', array_merge($data, [
            'slug' => slugify((string) $data['name']) . '-' . substr((string) time(), -4),
        ]));

        AuditService::log('create', 'financing', 'payment_method', $id, 'Método de pago creado: ' . $data['name']);

        $this->success('Método de pago creado.');
        $this->back();
    }

    public function updateMethod(string $id): void
    {
        $data = $this->validateMethod();

        Database::update('payment_methods', $data, 'id = :id', ['id' => (int) $id]);

        AuditService::log('update', 'financing', 'payment_method', (int) $id, 'Método de pago editado: ' . $data['name']);

        $this->success('Método de pago actualizado.');
        $this->back();
    }

    /** @return array<string,mixed> */
    private function validateMethod(): array
    {
        $data = $this->validate(Request::all(), [
            'name'             => 'required|string|min:2|max:120',
            'description'      => 'max:255',
            'icon'             => 'max:60',
            'discount_percent' => 'numeric|between:0,100',
            'sort_order'       => 'integer',
        ], ['name' => 'nombre']);

        return [
            'name'             => $data['name'],
            'description'      => $data['description'] ?? null,
            'icon'             => $data['icon'] ?? null,
            'discount_percent' => (float) ($data['discount_percent'] ?? 0),
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
            'active'           => Request::flag('active', true),
        ];
    }
}
