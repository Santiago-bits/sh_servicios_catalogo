<?php
/**
 * ARCHIVO: app/models/Machine.php
 * ---------------------------------------------------------------------
 * Tabla de extensión de products para maquinaria.
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Machine extends Model
{
    protected string $table = 'machines';
    protected string $primaryKey = 'product_id';

    protected array $fillable = [
        'product_id', 'model', 'year', 'serial_number', 'condition_type', 'hours',
        'fuel', 'engine', 'power_hp', 'transmission', 'capacity_kg', 'lift_height_mm',
        'closed_height_mm', 'weight_kg', 'length_mm', 'width_mm', 'turn_radius_mm',
        'battery', 'voltage', 'mast_type', 'tire_type', 'fork_size', 'tech_notes',
        'location', 'warranty',
    ];

    /** Crea o actualiza la ficha técnica de la máquina. @param array<string,mixed> $data */
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

        $sql = sprintf(
            'INSERT INTO machines (`%s`) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            implode('`, `', $columns),
            implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns)),
            implode(', ', $updates)
        );

        Database::query($sql, $data);
    }

    /** Sincroniza los repuestos compatibles de una máquina. @param array<int,int> $partIds */
    public function syncParts(int $machineId, array $partIds, array $recommended = []): void
    {
        Database::delete('machine_spare_parts', 'machine_id = :id', ['id' => $machineId]);

        foreach (array_unique(array_map('intval', $partIds)) as $partId) {
            if ($partId <= 0) {
                continue;
            }
            Database::execute(
                'INSERT IGNORE INTO machine_spare_parts (machine_id, spare_part_id, recommended) VALUES (:m, :p, :r)',
                ['m' => $machineId, 'p' => $partId, 'r' => in_array($partId, array_map('intval', $recommended), true) ? 1 : 0]
            );
        }
    }

    /** @return array<int,int> */
    public function partIds(int $machineId): array
    {
        $rows = Database::select('SELECT spare_part_id FROM machine_spare_parts WHERE machine_id = :id', ['id' => $machineId]);
        return array_map('intval', array_column($rows, 'spare_part_id'));
    }

    /** Modelos distintos cargados (para autocompletado y filtros). @return array<int,string> */
    public function distinctModels(): array
    {
        $rows = Database::select(
            'SELECT DISTINCT m.model FROM machines m
               INNER JOIN products p ON p.id = m.product_id
              WHERE m.model IS NOT NULL AND m.model <> \'\' AND p.active = 1
              ORDER BY m.model ASC'
        );
        return array_column($rows, 'model');
    }

    /** @return array<int,string> */
    public function distinctLocations(): array
    {
        $rows = Database::select(
            'SELECT DISTINCT location FROM machines
              WHERE location IS NOT NULL AND location <> \'\' ORDER BY location ASC'
        );
        return array_column($rows, 'location');
    }

    /** @return array{min:int,max:int} */
    public function yearRange(): array
    {
        $row = Database::selectOne('SELECT MIN(year) AS min_year, MAX(year) AS max_year FROM machines WHERE year IS NOT NULL');
        return ['min' => (int) ($row['min_year'] ?? 0), 'max' => (int) ($row['max_year'] ?? 0)];
    }
}
