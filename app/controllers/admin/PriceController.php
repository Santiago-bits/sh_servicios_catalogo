<?php
/**
 * ARCHIVO: app/controllers/admin/PriceController.php
 * ---------------------------------------------------------------------
 * Gestión de precios: edición rápida, ajuste masivo e historial.
 *
 * Quien no tiene el permiso prices.view_cost no recibe el costo ni la
 * ganancia: se filtran en el servidor antes de renderizar la vista.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\PriceHistory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\PriceService;
use Core\Auth;
use Core\Request;

class PriceController extends AdminController
{
    public function index(): void
    {
        $type = Request::get('tipo') === 'spare_part' ? 'spare_part' : 'machine';

        $filters = [
            'q'          => Request::get('q'),
            'categoria'  => Request::get('categoria'),
            'marca'      => Request::get('marca'),
            'sin_precio' => Request::get('sin_precio'),
            'orden'      => Request::get('orden', 'az'),
        ];

        $canSeeCost = Auth::canSeeCost();

        $result = (new Product())->catalog($type, $filters, max(1, Request::int('pagina', 1)), 30, true);

        if (!$canSeeCost) {
            $result['data'] = array_map(
                static fn (array $p): array => PriceService::publicView($p),
                $result['data']
            );
        }

        $this->view('admin/prices/index', [
            'pageTitle'  => 'Precios · Panel',
            'adminTitle' => 'Precios y ganancias',
            'robots'     => 'noindex, nofollow',
            'result'     => $result,
            'products'   => $result['data'],
            'filters'    => $filters,
            'type'       => $type,
            'categories' => (new Category())->ofType($type, false),
            'brands'     => (new Brand())->active(),
            'canSeeCost' => $canSeeCost,
            'canEdit'    => Auth::can('prices.edit'),
            'recent'     => $canSeeCost ? (new PriceHistory())->latest(8) : [],
        ]);
    }

    /** Edición de un precio puntual (AJAX o formulario). */
    public function update(string $id): void
    {
        $productModel = new Product();
        $product      = $productModel->find((int) $id);

        if ($product === null) {
            $this->json(['ok' => false, 'message' => 'El producto no existe.'], 404);
        }

        $reason = trim((string) Request::post('reason', ''));

        if (Auth::canSeeCost()) {
            $cost   = Request::float('cost_price');
            $profit = Request::float('profit_percent');
            $final  = Request::float('final_price');
        } else {
            // Sin acceso al costo sólo puede fijar el precio final
            $cost   = (float) $product['cost_price'];
            $profit = 0.0;
            $final  = Request::float('final_price');

            if ($final <= 0) {
                $this->json(['ok' => false, 'message' => 'Ingresá el precio final.'], 422);
            }
        }

        $result = PriceService::applyChange($product, $cost, $profit, $final > 0 ? $final : null, $reason);

        $payload = [
            'ok'      => true,
            'message' => 'Precio actualizado.',
            'prices'  => [
                'final_price'    => money($result['final_price'], (string) $product['currency']),
                'profit_amount'  => Auth::canSeeCost() ? money($result['profit_amount'], (string) $product['currency']) : null,
                'profit_percent' => Auth::canSeeCost() ? percent($result['profit_percent']) : null,
                'margin'         => Auth::canSeeCost() ? percent(PriceService::margin($result['cost_price'], $result['final_price'])) : null,
            ],
        ];

        if (Request::isAjax()) {
            $this->json($payload);
        }

        $this->success('Precio actualizado.');
        $this->back();
    }

    /** Ajuste masivo por porcentaje. */
    public function bulk(): void
    {
        $this->requirePermission('prices.edit');

        $percent = Request::float('percent');
        $target  = Request::post('target') === 'costo' ? 'costo' : 'precio';
        $reason  = trim((string) Request::post('reason', ''));

        if ($percent === 0.0) {
            $this->error('Ingresá un porcentaje distinto de cero.');
            $this->back();
        }

        if ($percent < -90 || $percent > 500) {
            $this->error('El porcentaje debe estar entre -90% y 500%.');
            $this->back();
        }

        if ($reason === '') {
            $this->error('Indicá el motivo del ajuste: queda registrado en el historial.');
            $this->back();
        }

        $updated = PriceService::bulkAdjust([
            'tipo'      => Request::post('tipo'),
            'categoria' => Request::int('categoria') ?: null,
            'marca'     => Request::int('marca') ?: null,
        ], $percent, $target, $reason);

        $this->success(sprintf(
            'Se ajustaron %d producto(s) un %s%% sobre el %s.',
            $updated,
            number_es($percent, 2),
            $target
        ));

        $this->back();
    }

    public function history(string $id): void
    {
        $this->requirePermission('prices.history');

        $product = (new Product())->findFull((int) $id);

        if ($product === null) {
            $this->abort(404, 'El producto no existe.');
        }

        if (!Auth::canSeeCost()) {
            $product = PriceService::publicView($product);
        }

        $this->view('admin/prices/history', [
            'pageTitle'  => 'Historial de precios · Panel',
            'adminTitle' => 'Historial de precios',
            'robots'     => 'noindex, nofollow',
            'product'    => $product,
            'history'    => (new PriceHistory())->forProduct((int) $id, 100),
            'canSeeCost' => Auth::canSeeCost(),
        ]);
    }
}
