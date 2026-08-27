<?php
/**
 * ARCHIVO: app/models/Service.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Service extends Model
{
    protected string $table = 'services';

    protected array $fillable = [
        'title', 'slug', 'icon', 'image', 'short_description', 'description',
        'bullets', 'featured', 'sort_order', 'active',
    ];

    protected array $sortable = ['id', 'title', 'sort_order'];

    /** @return array<int,array<string,mixed>> */
    public function activeList(?int $limit = null): array
    {
        $sql = 'SELECT * FROM services WHERE active = 1 ORDER BY featured DESC, sort_order ASC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }
        return Database::select($sql);
    }

    /** @return array<string,mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        return Database::selectOne('SELECT * FROM services WHERE slug = :slug AND active = 1 LIMIT 1', ['slug' => $slug]);
    }

    /** @return array<int,string> */
    public static function bullets(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_map('strval', $decoded) : [];
    }
}
