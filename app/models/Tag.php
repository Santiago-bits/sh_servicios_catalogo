<?php
/**
 * ARCHIVO: app/models/Tag.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Tag extends Model
{
    protected string $table = 'tags';
    protected array $fillable = ['name', 'slug', 'color', 'icon', 'active', 'sort_order'];
    protected array $sortable = ['id', 'name', 'sort_order'];

    /** @return array<int,array<string,mixed>> */
    public function active(): array
    {
        return Database::select('SELECT * FROM tags WHERE active = 1 ORDER BY sort_order ASC, name ASC');
    }

    /** @return array<int,array<string,mixed>> */
    public function withCounts(): array
    {
        return Database::select(
            'SELECT t.*, (SELECT COUNT(*) FROM product_tags pt WHERE pt.tag_id = t.id) AS products_count
               FROM tags t ORDER BY t.sort_order ASC, t.name ASC'
        );
    }

    /** Reemplaza las etiquetas de un producto. @param array<int,int> $tagIds */
    public function syncProduct(int $productId, array $tagIds): void
    {
        Database::delete('product_tags', 'product_id = :id', ['id' => $productId]);

        foreach (array_unique(array_map('intval', $tagIds)) as $tagId) {
            if ($tagId > 0) {
                Database::execute(
                    'INSERT IGNORE INTO product_tags (product_id, tag_id) VALUES (:p, :t)',
                    ['p' => $productId, 't' => $tagId]
                );
            }
        }
    }

    /** @return array<int,int> */
    public function idsForProduct(int $productId): array
    {
        $rows = Database::select('SELECT tag_id FROM product_tags WHERE product_id = :id', ['id' => $productId]);
        return array_map('intval', array_column($rows, 'tag_id'));
    }
}
