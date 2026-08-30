<?php
/**
 * ARCHIVO: app/models/Category.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Category extends Model
{
    protected string $table = 'categories';

    protected array $fillable = [
        'parent_id', 'type', 'name', 'slug', 'description', 'icon', 'image',
        'meta_title', 'meta_description', 'sort_order', 'featured', 'active',
    ];

    protected array $sortable = ['id', 'name', 'sort_order', 'created_at'];

    /** @return array<int,array<string,mixed>> */
    public function ofType(string $type, bool $onlyActive = true): array
    {
        $sql = 'SELECT c.*, (SELECT COUNT(*) FROM products p
                              WHERE p.category_id = c.id AND p.active = 1 AND p.deleted_at IS NULL) AS products_count
                  FROM categories c WHERE c.type = :type';
        if ($onlyActive) {
            $sql .= ' AND c.active = 1';
        }
        $sql .= ' ORDER BY c.sort_order ASC, c.name ASC';

        return Database::select($sql, ['type' => $type]);
    }

    /** Categorías destacadas con producto disponible. @return array<int,array<string,mixed>> */
    public function featuredWithProducts(string $type, int $limit = 8): array
    {
        return Database::select(
            'SELECT c.*, COUNT(p.id) AS products_count
               FROM categories c
               LEFT JOIN products p ON p.category_id = c.id AND p.active = 1 AND p.deleted_at IS NULL
              WHERE c.type = :type AND c.active = 1
              GROUP BY c.id
              ORDER BY c.featured DESC, products_count DESC, c.sort_order ASC
              LIMIT ' . max(1, $limit),
            ['type' => $type]
        );
    }

    /** @return array<string,mixed>|null */
    public function findBySlugAndType(string $slug, string $type): ?array
    {
        return Database::selectOne(
            'SELECT * FROM categories WHERE slug = :slug AND type = :type LIMIT 1',
            ['slug' => $slug, 'type' => $type]
        );
    }

    /** @return array<int,array<string,mixed>> Todas, con el nombre del padre (panel). */
    public function withParent(): array
    {
        return Database::select(
            'SELECT c.*, pc.name AS parent_name,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.deleted_at IS NULL) AS products_count
               FROM categories c
               LEFT JOIN categories pc ON pc.id = c.parent_id
              ORDER BY c.type ASC, c.sort_order ASC, c.name ASC'
        );
    }

    /** Siguiente número de orden libre dentro del tipo, para que una categoría nueva quede al final. */
    public function nextSortOrder(string $type): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories WHERE type = :type',
            ['type' => $type]
        );
    }

    public function hasProducts(int $id): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM products WHERE category_id = :id AND deleted_at IS NULL',
            ['id' => $id]
        ) > 0;
    }
}
