<?php
/**
 * ARCHIVO: app/models/Feature.php
 * ---------------------------------------------------------------------
 * Características técnicas dinámicas (modelo EAV). Permite agregar
 * "Altura replegada", "Radio de giro" o lo que haga falta sin tocar
 * la estructura de la tabla de productos.
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Feature extends Model
{
    protected string $table = 'features';

    protected array $fillable = [
        'name', 'slug', 'group_name', 'unit', 'input_type', 'options',
        'applies_to', 'filterable', 'comparable', 'public', 'sort_order', 'active',
    ];

    protected array $sortable = ['id', 'name', 'group_name', 'sort_order'];

    /** @return array<int,array<string,mixed>> */
    public function forType(string $type, bool $onlyActive = true): array
    {
        $sql = 'SELECT * FROM features WHERE (applies_to = :type OR applies_to = \'both\')';
        if ($onlyActive) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';

        return Database::select($sql, ['type' => $type]);
    }

    /** @return array<int,array<string,mixed>> */
    public function comparable(string $type = 'machine'): array
    {
        return Database::select(
            'SELECT * FROM features
              WHERE (applies_to = :type OR applies_to = \'both\') AND active = 1 AND comparable = 1
              ORDER BY sort_order ASC',
            ['type' => $type]
        );
    }

    /** Valores de un producto indexados por slug de característica. @return array<string,array<string,mixed>> */
    public function valuesFor(int $productId): array
    {
        $rows = Database::select(
            'SELECT f.slug, f.name, f.unit, f.id AS feature_id, fv.value_text, fv.value_number
               FROM feature_values fv INNER JOIN features f ON f.id = fv.feature_id
              WHERE fv.product_id = :id',
            ['id' => $productId]
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['slug']] = $row;
        }
        return $out;
    }

    /**
     * Guarda los valores enviados desde el formulario del panel.
     *
     * @param array<int|string,string> $values feature_id => valor
     */
    public function saveValues(int $productId, array $values): void
    {
        foreach ($values as $featureId => $value) {
            $featureId = (int) $featureId;
            if ($featureId <= 0) {
                continue;
            }

            $value = is_string($value) ? trim($value) : '';

            if ($value === '') {
                Database::delete('feature_values', 'product_id = :p AND feature_id = :f', ['p' => $productId, 'f' => $featureId]);
                continue;
            }

            $number = normalize_decimal($value, 0.0);
            $isNum  = is_numeric(str_replace([',', '.'], ['', '.'], $value)) || preg_match('/^[\d.,]+$/', $value) === 1;

            Database::execute(
                'INSERT INTO feature_values (product_id, feature_id, value_text, value_number)
                 VALUES (:p, :f, :t, :n)
                 ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), value_number = VALUES(value_number)',
                [
                    'p' => $productId,
                    'f' => $featureId,
                    't' => $value,
                    'n' => $isNum ? $number : null,
                ]
            );
        }
    }

    /** @return array<int,array<string,mixed>> Características usables como filtro. */
    public function filterable(string $type): array
    {
        return Database::select(
            'SELECT * FROM features
              WHERE (applies_to = :type OR applies_to = \'both\') AND active = 1 AND filterable = 1
              ORDER BY sort_order ASC',
            ['type' => $type]
        );
    }
}
