<?php
/**
 * ARCHIVO: app/services/AlertService.php
 * ---------------------------------------------------------------------
 * Alertas administrativas: lo que el equipo tiene que corregir o
 * atender hoy (stock bajo, productos sin precio, cotizaciones por
 * vencer, repuestos sin compatibilidad, etc.).
 */

declare(strict_types=1);

namespace App\Services;

use Core\Database;

final class AlertService
{
    /**
     * @return array<int,array{
     *   key:string, level:string, icon:string, title:string,
     *   count:int, url:string, items:array<int,array<string,mixed>>
     * }>
     */
    public static function all(int $itemsPerAlert = 5): array
    {
        $alerts = [];

        // --- Stock bajo ---------------------------------------------
        $lowStock = Database::select(
            'SELECT id, code, name, stock, stock_reserved, stock_min, (stock - stock_reserved) AS available
               FROM products
              WHERE type = \'spare_part\' AND active = 1 AND deleted_at IS NULL AND track_stock = 1
                AND (stock - stock_reserved) <= GREATEST(stock_min, 0)
              ORDER BY available ASC LIMIT ' . $itemsPerAlert
        );

        $lowStockTotal = (int) Database::scalar(
            'SELECT COUNT(*) FROM products
              WHERE type = \'spare_part\' AND active = 1 AND deleted_at IS NULL AND track_stock = 1
                AND (stock - stock_reserved) <= GREATEST(stock_min, 0)'
        );

        if ($lowStockTotal > 0) {
            $alerts[] = self::build('stock_bajo', 'danger', 'bi-battery-low', 'Repuestos con stock bajo', $lowStockTotal, admin_url('stock?filtro=bajo'), $lowStock);
        }

        // --- Productos sin precio ------------------------------------
        $noPrice = Database::select(
            'SELECT id, code, name, type FROM products
              WHERE final_price <= 0 AND active = 1 AND deleted_at IS NULL
              ORDER BY updated_at DESC LIMIT ' . $itemsPerAlert
        );
        $noPriceTotal = (int) Database::scalar(
            'SELECT COUNT(*) FROM products WHERE final_price <= 0 AND active = 1 AND deleted_at IS NULL'
        );

        if ($noPriceTotal > 0) {
            $alerts[] = self::build('sin_precio', 'warning', 'bi-tag', 'Productos sin precio cargado', $noPriceTotal, admin_url('precios?sin_precio=1'), $noPrice);
        }

        // --- Máquinas sin imágenes -----------------------------------
        $noImage = Database::select(
            'SELECT id, code, name, type FROM products p
              WHERE active = 1 AND deleted_at IS NULL
                AND NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)
              ORDER BY type ASC, updated_at DESC LIMIT ' . $itemsPerAlert
        );
        $noImageTotal = (int) Database::scalar(
            'SELECT COUNT(*) FROM products p WHERE active = 1 AND deleted_at IS NULL
               AND NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)'
        );

        if ($noImageTotal > 0) {
            $alerts[] = self::build('sin_imagen', 'warning', 'bi-image', 'Productos sin imágenes', $noImageTotal, admin_url('maquinaria?sin_imagen=1'), $noImage);
        }

        // --- Productos sin categoría ---------------------------------
        $noCategory = Database::select(
            'SELECT id, code, name, type FROM products
              WHERE category_id IS NULL AND active = 1 AND deleted_at IS NULL LIMIT ' . $itemsPerAlert
        );
        $noCategoryTotal = (int) Database::scalar(
            'SELECT COUNT(*) FROM products WHERE category_id IS NULL AND active = 1 AND deleted_at IS NULL'
        );

        if ($noCategoryTotal > 0) {
            $alerts[] = self::build('sin_categoria', 'info', 'bi-folder-x', 'Productos sin categoría', $noCategoryTotal, admin_url('maquinaria'), $noCategory);
        }

        // --- Repuestos sin compatibilidad ----------------------------
        $noCompat = Database::select(
            'SELECT id, code, name FROM products p
              WHERE p.type = \'spare_part\' AND p.active = 1 AND p.deleted_at IS NULL
                AND NOT EXISTS (SELECT 1 FROM spare_part_compatibility s WHERE s.spare_part_id = p.id)
                AND NOT EXISTS (SELECT 1 FROM machine_spare_parts m WHERE m.spare_part_id = p.id)
              ORDER BY p.updated_at DESC LIMIT ' . $itemsPerAlert
        );
        $noCompatTotal = (int) Database::scalar(
            'SELECT COUNT(*) FROM products p
              WHERE p.type = \'spare_part\' AND p.active = 1 AND p.deleted_at IS NULL
                AND NOT EXISTS (SELECT 1 FROM spare_part_compatibility s WHERE s.spare_part_id = p.id)
                AND NOT EXISTS (SELECT 1 FROM machine_spare_parts m WHERE m.spare_part_id = p.id)'
        );

        if ($noCompatTotal > 0) {
            $alerts[] = self::build('sin_compatibilidad', 'info', 'bi-diagram-3', 'Repuestos sin compatibilidad declarada', $noCompatTotal, admin_url('repuestos'), $noCompat);
        }

        // --- Consultas nuevas ----------------------------------------
        $newInquiries = Database::select(
            'SELECT id, name, company, subject, created_at FROM inquiries
              WHERE status = \'nueva\' ORDER BY created_at DESC LIMIT ' . $itemsPerAlert
        );
        $newInquiriesTotal = (int) Database::scalar('SELECT COUNT(*) FROM inquiries WHERE status = \'nueva\'');

        if ($newInquiriesTotal > 0) {
            $alerts[] = self::build('consultas', 'accent', 'bi-chat-dots-fill', 'Consultas sin responder', $newInquiriesTotal, admin_url('consultas?estado=nueva'), $newInquiries);
        }

        // --- Cotizaciones por vencer ---------------------------------
        $expiring = Database::select(
            'SELECT id, number, customer_name, valid_until FROM quotes
              WHERE status = \'enviada\' AND valid_until IS NOT NULL
                AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
              ORDER BY valid_until ASC LIMIT ' . $itemsPerAlert
        );

        if ($expiring !== []) {
            $alerts[] = self::build('cotizaciones', 'warning', 'bi-hourglass-split', 'Cotizaciones próximas a vencer', count($expiring), admin_url('cotizaciones?estado=enviada'), $expiring);
        }

        // --- Cambios de precio recientes -----------------------------
        $priceChanges = Database::select(
            'SELECT ph.id, ph.new_price, ph.old_price, ph.created_at, p.code, p.name, u.name AS user_name
               FROM price_history ph
               INNER JOIN products p ON p.id = ph.product_id
               LEFT JOIN users u ON u.id = ph.user_id
              WHERE ph.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              ORDER BY ph.created_at DESC LIMIT ' . $itemsPerAlert
        );

        if ($priceChanges !== []) {
            $alerts[] = self::build('precios', 'neutral', 'bi-graph-up-arrow', 'Precios modificados esta semana', count($priceChanges), admin_url('precios'), $priceChanges);
        }

        return $alerts;
    }

    /** Cantidad total de alertas "que exigen acción" (para el badge). */
    public static function urgentCount(): int
    {
        $total = 0;
        foreach (self::all(1) as $alert) {
            if (in_array($alert['level'], ['danger', 'warning', 'accent'], true)) {
                $total += $alert['count'];
            }
        }
        return $total;
    }

    /** @param array<int,array<string,mixed>> $items */
    private static function build(string $key, string $level, string $icon, string $title, int $count, string $url, array $items): array
    {
        return [
            'key'   => $key,
            'level' => $level,
            'icon'  => $icon,
            'title' => $title,
            'count' => $count,
            'url'   => $url,
            'items' => $items,
        ];
    }
}
