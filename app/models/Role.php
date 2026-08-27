<?php
/**
 * ARCHIVO: app/models/Role.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Role extends Model
{
    protected string $table = 'roles';
    protected array $fillable = ['name', 'slug', 'description', 'is_system', 'active'];
    protected array $sortable = ['id', 'name'];

    /** @return array<int,array<string,mixed>> */
    public function allWithCounts(): array
    {
        return Database::select(
            'SELECT r.*,
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS users_count,
                    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) AS permissions_count
               FROM roles r ORDER BY r.id ASC'
        );
    }

    /** Todos los permisos agrupados por módulo. @return array<string,array<int,array<string,mixed>>> */
    public function allPermissions(): array
    {
        $rows    = Database::select('SELECT * FROM permissions ORDER BY module ASC, id ASC');
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }

        return $grouped;
    }

    /** @return array<int,int> */
    public function permissionIds(int $roleId): array
    {
        $rows = Database::select('SELECT permission_id FROM role_permissions WHERE role_id = :id', ['id' => $roleId]);
        return array_map('intval', array_column($rows, 'permission_id'));
    }

    /** @param array<int,int> $permissionIds */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        Database::delete('role_permissions', 'role_id = :id', ['id' => $roleId]);

        foreach (array_unique(array_map('intval', $permissionIds)) as $permissionId) {
            if ($permissionId > 0) {
                Database::execute(
                    'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:r, :p)',
                    ['r' => $roleId, 'p' => $permissionId]
                );
            }
        }
    }

    public function hasUsers(int $roleId): bool
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM users WHERE role_id = :id', ['id' => $roleId]) > 0;
    }
}
