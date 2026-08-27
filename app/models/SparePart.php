<?php
/**
 * ARCHIVO: app/models/SparePart.php
 * ---------------------------------------------------------------------
 * Tabla de extensión de products para repuestos + códigos OEM y
 * compatibilidades.
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class SparePart extends Model
{
    protected string $table = 'spare_parts';
    protected string $primaryKey = 'product_id';

    protected array $fillable = [
        'product_id', 'oem_code', 'manufacturer_code', 'manufacturer', 'origin',
        'unit', 'weight_kg', 'warehouse_id', 'sector', 'shelf', 'position', 'lead_time_days',
    ];

    /** @param array<string,mixed> $data */
    public function save(int $productId, array $data): void
    {
        $data = $this->filterFillable($data);
        $data['product_id'] = $productId;

        $columns = array_keys($data);
        $updates = [];
        foreach ($columns as $column) {
            if ($column !== 'product_id') {
                $updates[] = sprintf('`%s` = VALUES(`%s`)', $column, $column);
            }
        }

        Database::query(
            sprintf(
                'INSERT INTO spare_parts (`%s`) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
                implode('`, `', $columns),
                implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns)),
                implode(', ', $updates)
            ),
            $data
        );
    }

    // ----------------------------------------------------------------
    // Códigos
    // ----------------------------------------------------------------

    public function addCode(int $productId, string $type, string $code, ?int $brandId = null, ?string $note = null): int
    {
        $valid = ['interno', 'oem', 'fabricante', 'alternativo', 'cruzado'];

        return Database::insert('spare_part_codes', [
            'product_id' => $productId,
            'code_type'  => in_array($type, $valid, true) ? $type : 'alternativo',
            'code'       => mb_substr(trim($code), 0, 80),
            'brand_id'   => $brandId ?: null,
            'note'       => $note !== null ? mb_substr($note, 0, 160) : null,
        ]);
    }

    public function deleteCode(int $codeId, int $productId): int
    {
        return Database::delete('spare_part_codes', 'id = :id AND product_id = :p', ['id' => $codeId, 'p' => $productId]);
    }

    // ----------------------------------------------------------------
    // Compatibilidad
    // ----------------------------------------------------------------

    public function addCompatibility(int $partId, ?int $brandId, string $model, ?int $yearFrom = null, ?int $yearTo = null, ?string $note = null): int
    {
        return Database::insert('spare_part_compatibility', [
            'spare_part_id' => $partId,
            'brand_id'      => $brandId ?: null,
            'model'         => mb_substr(trim($model), 0, 120),
            'year_from'     => $yearFrom ?: null,
            'year_to'       => $yearTo ?: null,
            'note'          => $note !== null && $note !== '' ? mb_substr($note, 0, 200) : null,
        ]);
    }

    public function deleteCompatibility(int $id, int $partId): int
    {
        return Database::delete('spare_part_compatibility', 'id = :id AND spare_part_id = :p', ['id' => $id, 'p' => $partId]);
    }

    /** Sincroniza las máquinas del catálogo asociadas a un repuesto. @param array<int,int> $machineIds */
    public function syncMachines(int $partId, array $machineIds): void
    {
        Database::delete('machine_spare_parts', 'spare_part_id = :id', ['id' => $partId]);

        foreach (array_unique(array_map('intval', $machineIds)) as $machineId) {
            if ($machineId > 0) {
                Database::execute(
                    'INSERT IGNORE INTO machine_spare_parts (machine_id, spare_part_id) VALUES (:m, :p)',
                    ['m' => $machineId, 'p' => $partId]
                );
            }
        }
    }

    /** @return array<int,int> */
    public function machineIds(int $partId): array
    {
        $rows = Database::select('SELECT machine_id FROM machine_spare_parts WHERE spare_part_id = :id', ['id' => $partId]);
        return array_map('intval', array_column($rows, 'machine_id'));
    }

    /** Repuestos sin ninguna compatibilidad cargada (alerta del panel). */
    public function withoutCompatibility(int $limit = 20): array
    {
        return Database::select(
            'SELECT p.id, p.code, p.name FROM products p
              WHERE p.type = \'spare_part\' AND p.active = 1 AND p.deleted_at IS NULL
                AND NOT EXISTS (SELECT 1 FROM spare_part_compatibility s WHERE s.spare_part_id = p.id)
                AND NOT EXISTS (SELECT 1 FROM machine_spare_parts m WHERE m.spare_part_id = p.id)
              ORDER BY p.updated_at DESC LIMIT ' . max(1, $limit)
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function warehouses(): array
    {
        return Database::select('SELECT * FROM warehouses WHERE active = 1 ORDER BY name ASC');
    }
}
