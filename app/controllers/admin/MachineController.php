<?php
/**
 * ARCHIVO: app/controllers/admin/MachineController.php
 * ---------------------------------------------------------------------
 * ABM de maquinaria. Hereda el grueso de ProductAdminController y
 * agrega la ficha técnica propia de las máquinas y la vinculación con
 * los repuestos compatibles.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Machine;
use App\Models\Product;
use Core\Database;

class MachineController extends ProductAdminController
{
    protected string $type          = 'machine';
    protected string $routeBase     = 'maquinaria';
    protected string $uploadFolder  = 'machines';
    protected string $permission    = 'machines';
    protected string $labelSingular = 'máquina';
    protected string $labelPlural   = 'Maquinaria';

    protected function extraRules(): array
    {
        return [
            'model' => 'max:120',
            'year'  => 'integer|between:1950,2100',
        ];
    }

    /** @param array<string,mixed> $input */
    protected function saveTypeData(int $productId, array $input): void
    {
        $fuels      = ['electrico', 'diesel', 'nafta', 'gas', 'glp', 'hibrido', 'manual'];
        $conditions = ['nuevo', 'usado', 'reacondicionado'];

        (new Machine())->save($productId, [
            'model'            => $this->text($input, 'model', 120),
            'year'             => $this->int($input, 'year'),
            'serial_number'    => $this->text($input, 'serial_number', 80),
            'condition_type'   => in_array($input['condition_type'] ?? '', $conditions, true) ? $input['condition_type'] : 'usado',
            'hours'            => $this->int($input, 'hours'),
            'fuel'             => in_array($input['fuel'] ?? '', $fuels, true) ? $input['fuel'] : null,
            'engine'           => $this->text($input, 'engine', 120),
            'power_hp'         => $this->decimal($input, 'power_hp'),
            'transmission'     => $this->text($input, 'transmission', 120),
            'capacity_kg'      => $this->decimal($input, 'capacity_kg'),
            'lift_height_mm'   => $this->int($input, 'lift_height_mm'),
            'closed_height_mm' => $this->int($input, 'closed_height_mm'),
            'weight_kg'        => $this->decimal($input, 'weight_kg'),
            'length_mm'        => $this->int($input, 'length_mm'),
            'width_mm'         => $this->int($input, 'width_mm'),
            'turn_radius_mm'   => $this->int($input, 'turn_radius_mm'),
            'battery'          => $this->text($input, 'battery', 120),
            'voltage'          => $this->text($input, 'voltage', 40),
            'mast_type'        => $this->text($input, 'mast_type', 80),
            'tire_type'        => $this->text($input, 'tire_type', 80),
            'location'         => $this->text($input, 'location', 160),
            'warranty'         => $this->text($input, 'warranty', 160),
        ]);

        // Repuestos compatibles seleccionados
        (new Machine())->syncParts(
            $productId,
            array_map('intval', (array) ($input['spare_parts'] ?? [])),
            array_map('intval', (array) ($input['recommended_parts'] ?? []))
        );

        // Videos (una URL de YouTube por línea)
        $this->saveVideos($productId, (string) ($input['videos'] ?? ''));
    }

    /** @return array<string,mixed> */
    protected function formExtras(?array $product): array
    {
        $productId = $product !== null ? (int) $product['id'] : 0;

        return [
            'availableParts' => Database::select(
                'SELECT p.id, p.code, p.name, c.name AS category_name
                   FROM products p LEFT JOIN categories c ON c.id = p.category_id
                  WHERE p.type = \'spare_part\' AND p.deleted_at IS NULL
                  ORDER BY c.name ASC, p.name ASC'
            ),
            'selectedParts'    => $productId > 0 ? (new Machine())->partIds($productId) : [],
            'recommendedParts' => $productId > 0
                ? array_map('intval', array_column(Database::select(
                    'SELECT spare_part_id FROM machine_spare_parts WHERE machine_id = :id AND recommended = 1',
                    ['id' => $productId]
                ), 'spare_part_id'))
                : [],
            'videos' => $productId > 0
                ? implode("\n", array_map(
                    static fn (array $v): string => $v['provider'] === 'youtube'
                        ? 'https://www.youtube.com/watch?v=' . $v['video_ref']
                        : (string) $v['video_ref'],
                    (new Product())->videos($productId)
                ))
                : '',
            'fuels' => [
                ''          => 'Sin especificar',
                'electrico' => 'Eléctrico',
                'diesel'    => 'Diésel',
                'nafta'     => 'Nafta',
                'gas'       => 'Gas',
                'glp'       => 'GLP',
                'hibrido'   => 'Híbrido',
                'manual'    => 'Manual',
            ],
            'conditions' => [
                'nuevo'           => 'Nuevo',
                'usado'           => 'Usado',
                'reacondicionado' => 'Reacondicionado',
            ],
        ];
    }

    private function saveVideos(int $productId, string $raw): void
    {
        Database::delete('videos', 'product_id = :id', ['id' => $productId]);

        $lines = array_filter(array_map('trim', explode("\n", $raw)));
        $order = 0;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $provider = 'youtube';
            $ref      = $line;

            if (preg_match('~(?:youtube\.com/watch\?(?:[^\s]*&)?v=|youtu\.be/|youtube\.com/(?:embed|shorts|live|v)/)([A-Za-z0-9_-]{6,20})~i', $line, $m)) {
                $ref = $m[1];
            } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $line, $m)) {
                $provider = 'vimeo';
                $ref      = $m[1];
            } elseif (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $line)) {
                continue; // no parece un video válido
            }

            Database::insert('videos', [
                'product_id' => $productId,
                'title'      => 'Ver la máquina trabajando',
                'provider'   => $provider,
                'video_ref'  => $ref,
                'sort_order' => $order++,
            ]);
        }
    }

    // ----------------------------------------------------------------
    // Helpers de conversión
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
