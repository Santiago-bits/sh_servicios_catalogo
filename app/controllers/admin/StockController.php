<?php
/**
 * ARCHIVO: app/controllers/admin/StockController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Core\Database;
use Core\Request;

class StockController extends AdminController
{
    public function index(): void
    {
        $filters = [
            'q'    => Request::get('q'),
            'tipo' => Request::get('tipo'),
        ];

        $onlyLow = Request::get('filtro') === 'bajo';

        // Listado de repuestos con su situación de stock
        $conditions = ['p.type = \'spare_part\'', 'p.deleted_at IS NULL'];
        $params     = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(p.name LIKE :q OR p.code LIKE :q2 OR sp.oem_code LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params += ['q' => $like, 'q2' => $like, 'q3' => $like];
        }

        if ($onlyLow) {
            $conditions[] = 'p.track_stock = 1 AND (p.stock - p.stock_reserved) <= GREATEST(p.stock_min, 0)';
        }

        $where = implode(' AND ', $conditions);

        $sql = 'SELECT p.id, p.code, p.name, p.slug, p.stock, p.stock_reserved, p.stock_min, p.track_stock,
                       p.final_price, p.currency, c.name AS category_name,
                       w.name AS warehouse_name, sp.sector, sp.shelf, sp.position,
                       (p.stock - p.stock_reserved) AS available
                  FROM products p
                  LEFT JOIN categories c   ON c.id = p.category_id
                  LEFT JOIN spare_parts sp ON sp.product_id = p.id
                  LEFT JOIN warehouses w   ON w.id = sp.warehouse_id
                 WHERE ' . $where . '
                 ORDER BY (p.track_stock = 1 AND (p.stock - p.stock_reserved) <= GREATEST(p.stock_min, 0)) DESC,
                          available ASC, p.name ASC';

        $countSql = 'SELECT COUNT(*) FROM products p LEFT JOIN spare_parts sp ON sp.product_id = p.id WHERE ' . $where;

        $result = Product::paginateRaw($sql, $countSql, $params, max(1, Request::int('pagina', 1)), 30);

        $movementModel = new StockMovement();

        $this->view('admin/stock/index', [
            'pageTitle'  => 'Stock · Panel',
            'adminTitle' => 'Control de stock',
            'robots'     => 'noindex, nofollow',
            'result'     => $result,
            'products'   => $result['data'],
            'filters'    => $filters,
            'onlyLow'    => $onlyLow,
            'lowCount'   => $movementModel->countLowStock(),
            'movements'  => $movementModel->search([], 1, 15)['data'],
            'types'      => StockService::TYPES,
        ]);
    }

    public function store(): void
    {
        $productId = Request::int('product_id');
        $type      = (string) Request::post('type', '');
        $quantity  = (int) Request::float('quantity');

        $result = StockService::move(
            $productId,
            $type,
            $quantity,
            (string) Request::post('reason', ''),
            (string) Request::post('reference', '') ?: null
        );

        if (Request::isAjax()) {
            $this->json($result, $result['ok'] ? 200 : 422);
        }

        if ($result['ok']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }

        $this->back();
    }

    public function history(string $id): void
    {
        $product = (new Product())->findFull((int) $id);

        if ($product === null) {
            $this->abort(404, 'El producto no existe.');
        }

        // Evolución del stock para el gráfico
        $movements = (new StockMovement())->forProduct((int) $id, 200);

        $this->view('admin/stock/history', [
            'pageTitle'  => 'Historial de stock · Panel',
            'adminTitle' => 'Historial de stock',
            'robots'     => 'noindex, nofollow',
            'product'    => $product,
            'movements'  => $movements,
            'types'      => StockService::TYPES,
            'chart'      => [
                'labels' => array_reverse(array_map(
                    static fn (array $m): string => date('d/m', strtotime((string) $m['created_at'])),
                    $movements
                )),
                'values' => array_reverse(array_map(
                    static fn (array $m): int => (int) $m['stock_after'],
                    $movements
                )),
            ],
        ]);
    }
}
