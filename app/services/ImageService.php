<?php
/**
 * ARCHIVO: app/services/ImageService.php
 * ---------------------------------------------------------------------
 * Gestión de la galería de imágenes de un producto (alta, orden,
 * imagen principal y borrado del archivo físico).
 */

declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Uploader;

final class ImageService
{
    public const ZONES = [
        ''            => 'Sin clasificar',
        'exterior'    => 'Exterior',
        'interior'    => 'Interior',
        'motor'       => 'Motor',
        'tablero'     => 'Tablero',
        'ruedas'      => 'Ruedas',
        'horquillas'  => 'Horquillas',
        'mastil'      => 'Mástil',
        'accesorios'  => 'Accesorios',
        'detalle'     => 'Detalle',
    ];

    /**
     * Sube y asocia una o varias imágenes a un producto.
     *
     * @param array<int,array<string,mixed>> $files
     * @return array{uploaded:int,errors:array<int,string>}
     */
    public static function attach(int $productId, array $files, string $folder, ?string $zone = null): array
    {
        $uploaded = 0;
        $errors   = [];

        $hasMain = (int) Database::scalar(
            'SELECT COUNT(*) FROM product_images WHERE product_id = :id AND is_main = 1',
            ['id' => $productId]
        ) > 0;

        $sortOrder = (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = :id',
            ['id' => $productId]
        );

        foreach ($files as $file) {
            $result = Uploader::image($file, $folder);

            if (!$result['ok']) {
                $errors[] = ($file['name'] ?? 'archivo') . ': ' . $result['message'];
                continue;
            }

            Database::insert('product_images', [
                'product_id' => $productId,
                'path'       => $result['path'],
                'thumb_path' => $result['thumb'] ?? null,
                'alt'        => null,
                'zone'       => $zone !== null && $zone !== '' ? $zone : null,
                'is_main'    => $hasMain ? 0 : 1,
                'sort_order' => ++$sortOrder,
            ]);

            $hasMain = true;
            $uploaded++;
        }

        return ['uploaded' => $uploaded, 'errors' => $errors];
    }

    public static function setMain(int $productId, int $imageId): void
    {
        Database::execute('UPDATE product_images SET is_main = 0 WHERE product_id = :id', ['id' => $productId]);
        Database::execute(
            'UPDATE product_images SET is_main = 1 WHERE id = :img AND product_id = :id',
            ['img' => $imageId, 'id' => $productId]
        );
    }

    public static function remove(int $productId, int $imageId): bool
    {
        $image = Database::selectOne(
            'SELECT * FROM product_images WHERE id = :img AND product_id = :id',
            ['img' => $imageId, 'id' => $productId]
        );

        if ($image === null) {
            return false;
        }

        Uploader::delete((string) $image['path']);
        Uploader::delete($image['thumb_path'] !== null ? (string) $image['thumb_path'] : null);

        Database::delete('product_images', 'id = :img', ['img' => $imageId]);

        // Si se borró la principal, se promueve la primera que quede
        if ((int) $image['is_main'] === 1) {
            $next = Database::selectOne(
                'SELECT id FROM product_images WHERE product_id = :id ORDER BY sort_order ASC LIMIT 1',
                ['id' => $productId]
            );
            if ($next !== null) {
                self::setMain($productId, (int) $next['id']);
            }
        }

        return true;
    }

    /** Borra todas las imágenes de un producto (al eliminarlo). */
    public static function removeAll(int $productId): void
    {
        $images = Database::select('SELECT * FROM product_images WHERE product_id = :id', ['id' => $productId]);

        foreach ($images as $image) {
            Uploader::delete((string) $image['path']);
            Uploader::delete($image['thumb_path'] !== null ? (string) $image['thumb_path'] : null);
        }

        Database::delete('product_images', 'product_id = :id', ['id' => $productId]);
    }

    /** @param array<int,int> $order ids en el orden deseado */
    public static function reorder(int $productId, array $order): void
    {
        foreach (array_values($order) as $position => $imageId) {
            Database::execute(
                'UPDATE product_images SET sort_order = :pos WHERE id = :img AND product_id = :id',
                ['pos' => $position + 1, 'img' => (int) $imageId, 'id' => $productId]
            );
        }
    }
}
