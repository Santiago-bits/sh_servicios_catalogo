<?php
/**
 * ARCHIVO: app/services/AuditService.php
 * ---------------------------------------------------------------------
 * Registro de auditoría: quién hizo qué, cuándo, desde dónde.
 */

declare(strict_types=1);

namespace App\Services;

use Core\Auth;
use Core\Database;
use Core\Request;

final class AuditService
{
    /**
     * @param array<string,mixed> $data Datos adicionales (se guardan como JSON)
     */
    public static function log(
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        string $description = '',
        array $data = [],
        ?int $userId = null
    ): void {
        try {
            $userId ??= Auth::id();
            $userName = $userId !== null ? (Auth::id() === $userId ? Auth::name() : self::userName($userId)) : null;

            Database::insert('activity_logs', [
                'user_id'     => $userId,
                'user_name'   => $userName,
                'action'      => mb_substr($action, 0, 60),
                'module'      => mb_substr($module, 0, 60),
                'entity_type' => $entityType !== null ? mb_substr($entityType, 0, 60) : null,
                'entity_id'   => $entityId,
                'description' => mb_substr($description, 0, 255),
                'data'        => $data === [] ? null : json_encode($data, JSON_UNESCAPED_UNICODE),
                'ip'          => Request::ip(),
                'user_agent'  => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // La auditoría nunca debe romper la operación principal.
            error_log('[AUDIT] ' . $e->getMessage());
        }
    }

    /** Compara dos arrays y registra sólo lo que cambió. */
    public static function logChanges(
        string $module,
        string $entityType,
        int $entityId,
        array $before,
        array $after,
        string $description = ''
    ): void {
        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$key] = ['antes' => $oldValue, 'ahora' => $newValue];
            }
        }

        if ($changes === []) {
            return;
        }

        self::log('update', $module, $entityType, $entityId, $description, $changes);
    }

    private static function userName(int $userId): ?string
    {
        $name = Database::scalar('SELECT name FROM users WHERE id = :id', ['id' => $userId]);
        return $name === null ? null : (string) $name;
    }

    // -----------------------------------------------------------------
    //  Retención semanal
    // -----------------------------------------------------------------

    /** El registro de auditoría sólo guarda la última semana. */
    private const RETENTION_DAYS = 7;

    /** No hace falta revisar más seguido que esto para borrar lo vencido. */
    private const PURGE_CHECK_EVERY = 86400; // 1 día

    private const PURGE_MARKER = STORAGE_PATH . '/cache/audit-purge.marker';

    /**
     * Se llama en cada request (igual que ExchangeRateService::refreshIfStale()):
     * como mucho una vez por día, borra los registros de auditoría con más
     * de RETENTION_DAYS días. Nunca rompe la página si algo falla.
     */
    public static function purgeIfDue(): void
    {
        try {
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            if ($method !== 'GET' || str_starts_with(Request::uri(), '/api')) {
                return;
            }

            $last = is_file(self::PURGE_MARKER) ? (int) @filemtime(self::PURGE_MARKER) : 0;
            if ($last > 0 && (time() - $last) < self::PURGE_CHECK_EVERY) {
                return;
            }

            $dir = dirname(self::PURGE_MARKER);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @touch(self::PURGE_MARKER);

            $limit = date('Y-m-d H:i:s', time() - self::RETENTION_DAYS * 86400);
            Database::delete('activity_logs', 'created_at < :limite', ['limite' => $limit]);
        } catch (\Throwable $e) {
            error_log('[AUDIT] purgeIfDue: ' . $e->getMessage());
        }
    }
}
