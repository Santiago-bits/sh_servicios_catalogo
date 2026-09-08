<?php
/**
 * ARCHIVO: app/controllers/admin/PartController.php
 * ---------------------------------------------------------------------
 * ABM de repuestos: datos propios, códigos OEM/alternativos y
 * compatibilidad con modelos de máquina.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\SparePart;
use App\Services\AuditService;
use Core\Database;
use Core\Request;

class PartController extends ProductAdminController
{
    protected string $type          = 'spare_part';
    protected string $routeBase     = 'repuestos';
    protected string $uploadFolder  = 'parts';
    protected string $permission    = 'parts';
    protected string $labelSingular = 'repuesto';
    protected string $labelPlural   = 'Repuestos';

    protected function extraRules(): array
    {
        return [
            'oem_code'          => 'max:80',
            'manufacturer_code' => 'max:80',
            'manufacturer'      => 'max:120',
        ];
    }

    /**
     * Guarda los datos propios del repuesto. Sólo toca lo que venga en
     * el formulario: el ABM simplificado no manda códigos, así que esos
     * valores se conservan.
     *
     * @param array<string,mixed> $input
     */
    protected function saveTypeData(int $productId, array $input): void
    {
        $origins = ['original', 'alternativo', 'remanufacturado'];
        $map = [
            'oem_code'          => fn () => $this->text($input, 'oem_code', 80),
            'manufacturer_code' => fn () => $this->text($input, 'manufacturer_code', 80),
            'manufacturer'      => fn () => $this->text($input, 'manufacturer', 120),
            'origin'            => fn () => in_array($input['origin'] ?? '', $origins, true) ? $input['origin'] : 'alternativo',
            'unit'             => fn () => $this->text($input, 'unit', 20) ?? 'unidad',
            'weight_kg'        => fn () => $this->decimal($input, 'weight_kg'),
        ];

        $data = [];
        foreach ($map as $key => $resolver) {
            if (array_key_exists($key, $input)) {
                $data[$key] = $resolver();
            }
        }

        if ($data !== []) {
            (new SparePart())->save($productId, $data);
        } else {
            // Sin campos técnicos en el form: sólo asegurar que exista la fila.
            Database::query(
                'INSERT IGNORE INTO spare_parts (product_id, origin, unit) VALUES (:id, \'alternativo\', \'unidad\')',
                ['id' => $productId]
            );
        }
    }

    /** @return array<string,mixed> */
    protected function formExtras(?array $product): array
    {
        $productId = $product !== null ? (int) $product['id'] : 0;
        $model     = new Product();

        return [
            'codes'           => $productId > 0 ? $model->codes($productId) : [],
            'compatibility'   => $productId > 0 ? $model->compatibilityList($productId) : [],
            'availableMachines' => Database::select(
                'SELECT p.id, p.code, p.name, m.model
                   FROM products p INNER JOIN machines m ON m.product_id = p.id
                  WHERE p.type = \'machine\' AND p.deleted_at IS NULL
                  ORDER BY p.name ASC'
            ),
            'selectedMachines' => $productId > 0 ? (new SparePart())->machineIds($productId) : [],
            'allBrands'        => (new Brand())->active(),
            'manufacturers'    => array_column(Database::select(
                "SELECT DISTINCT manufacturer FROM spare_parts
                  WHERE manufacturer IS NOT NULL AND manufacturer <> ''
                  ORDER BY manufacturer ASC"
            ), 'manufacturer'),
            'origins'          => [
                'original'        => 'Original',
                'alternativo'     => 'Alternativo',
                'remanufacturado' => 'Remanufacturado',
            ],
            'units' => ['unidad' => 'Unidad', 'par' => 'Par', 'juego' => 'Juego', 'kit' => 'Kit', 'metro' => 'Metro', 'litro' => 'Litro'],
        ];
    }

    protected function saveRelations(int $productId, array $input): void
    {
        parent::saveRelations($productId, $input);

        // Sólo sincroniza compatibilidad si el formulario la maneja.
        if (array_key_exists('machines', $input)) {
            (new SparePart())->syncMachines($productId, array_map('intval', (array) $input['machines']));
        }
    }

    // ----------------------------------------------------------------
    // Códigos
    // ----------------------------------------------------------------

    public function addCode(string $id): void
    {
        $this->ensureExists((int) $id);

        $code = trim((string) Request::post('code', ''));
        if ($code === '') {
            $this->error('Ingresá el código.');
            $this->back();
        }

        (new SparePart())->addCode(
            (int) $id,
            (string) Request::post('code_type', 'alternativo'),
            $code,
            Request::int('brand_id') ?: null,
            (string) Request::post('note', '') ?: null
        );

        AuditService::log('update', 'parts', 'product', (int) $id, 'Código agregado: ' . $code);

        $this->success('Código agregado.');
        $this->back();
    }

    public function deleteCode(string $id, string $codeId): void
    {
        $this->ensureExists((int) $id);
        (new SparePart())->deleteCode((int) $codeId, (int) $id);

        $this->success('Código eliminado.');
        $this->back();
    }

    // ----------------------------------------------------------------
    // Compatibilidad
    // ----------------------------------------------------------------

    public function addCompatibility(string $id): void
    {
        $this->ensureExists((int) $id);

        $model = trim((string) Request::post('model', ''));
        if ($model === '') {
            $this->error('Ingresá el modelo de máquina.');
            $this->back();
        }

        (new SparePart())->addCompatibility(
            (int) $id,
            Request::int('brand_id') ?: null,
            $model,
            Request::int('year_from') ?: null,
            Request::int('year_to') ?: null,
            (string) Request::post('note', '') ?: null
        );

        AuditService::log('update', 'parts', 'product', (int) $id, 'Compatibilidad agregada: ' . $model);

        $this->success('Compatibilidad agregada.');
        $this->back();
    }

    public function deleteCompatibility(string $id, string $compatId): void
    {
        $this->ensureExists((int) $id);
        (new SparePart())->deleteCompatibility((int) $compatId, (int) $id);

        $this->success('Compatibilidad eliminada.');
        $this->back();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function text(array $input, string $key, int $max): ?string
    {
        $value = trim((string) ($input[$key] ?? ''));
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function int(array $input, string $key): ?int
    {
        $value = trim((string) ($input[$key] ?? ''));
        return $value === '' ? null : (int) normalize_decimal($value);
    }

    private function decimal(array $input, string $key): ?float
    {
        $value = trim((string) ($input[$key] ?? ''));
        return $value === '' ? null : normalize_decimal($value);
    }
}
