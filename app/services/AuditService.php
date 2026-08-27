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
}
