<?php
/**
 * ARCHIVO: app/models/Brand.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Brand extends Model
{
    protected string $table = 'brands';

    protected array $fillable = [
        'name', 'slug', 'logo', 'description', 'website', 'country',
        'featured', 'sort_order', 'active',
    ];

    protected array $sortable = ['id', 'name', 'sort_order', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public function active(): array
    {
        return Database::select('SELECT * FROM brands WHERE active = 1 ORDER BY sort_order ASC, name ASC');
    }

    /** Siguiente número de orden libre: para que una marca nueva quede al final. */
    public function nextSortOrder(): int
    {
        return (int) Database::scalar('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM brands');
    }

    /** @return array<int,array<string,mixed>> */
    public function withCounts(): array
    {
        return Database::select(
            'SELECT b.*,
                    SUM(CASE WHEN p.type = \'machine\'    AND p.deleted_at IS NULL THEN 1 ELSE 0 END) AS machines_count,
                    SUM(CASE WHEN p.type = \'spare_part\' AND p.deleted_at IS NULL THEN 1 ELSE 0 END) AS parts_count
               FROM brands b
               LEFT JOIN products p ON p.brand_id = b.id
              GROUP BY b.id
              ORDER BY b.sort_order ASC, b.name ASC'
        );
    }

    /**
     * Marcas para la grilla de la home. Se muestran TODAS las activas.
     * El orden es el del campo "Orden" y, a igualdad, el de creación:
     * así una marca nueva aparece siempre debajo de las que ya están.
     *
     * @param int $limit 0 = sin límite (todas).
     * @return array<int,array<string,mixed>>
     */
    public function forHomepage(int $limit = 0): array
    {
        $sql = 'SELECT * FROM brands WHERE active = 1 ORDER BY sort_order ASC, id ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        return Database::select($sql);
    }
}
