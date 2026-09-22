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
        'bullets', 'featured', 'show_clients', 'sort_order', 'active',
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

    /** Galería de fotos del servicio. @return array<int,array<string,mixed>> */
    public function images(int $serviceId): array
    {
        try {
            return Database::select(
                'SELECT * FROM service_images WHERE service_id = :id ORDER BY sort_order ASC, id ASC',
                ['id' => $serviceId]
            );
        } catch (\Throwable $e) {
            return []; // migración todavía no aplicada
        }
    }

    public function addImage(int $serviceId, string $path, ?string $thumb): int
    {
        $next = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM service_images WHERE service_id = :id',
            ['id' => $serviceId]
        );

        return Database::insert('service_images', [
            'service_id' => $serviceId,
            'path'       => $path,
            'thumb'      => $thumb,
            'sort_order' => $next,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findImage(int $serviceId, int $imageId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM service_images WHERE id = :img AND service_id = :id LIMIT 1',
            ['img' => $imageId, 'id' => $serviceId]
        );
    }

    public function deleteImage(int $imageId): void
    {
        Database::delete('service_images', 'id = :id', ['id' => $imageId]);
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
