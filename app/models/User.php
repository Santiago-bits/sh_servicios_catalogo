<?php
/**
 * ARCHIVO: app/models/User.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'role_id', 'name', 'email', 'password', 'phone', 'avatar', 'position',
        'active', 'must_change_pw', 'failed_logins', 'locked_until',
    ];

    protected array $sortable = ['id', 'name', 'email', 'created_at', 'last_login_at'];

    /** @return array<int,array<string,mixed>> */
    public function allWithRole(): array
    {
        return Database::select(
            'SELECT u.id, u.name, u.email, u.phone, u.position, u.active, u.last_login_at,
                    u.last_login_ip, u.created_at, u.locked_until,
                    r.name AS role_name, r.slug AS role_slug, r.id AS role_id
               FROM users u INNER JOIN roles r ON r.id = u.role_id
              ORDER BY u.name ASC'
        );
    }

    /** @return array<string,mixed>|null */
    public function findWithRole(int $id): ?array
    {
        return Database::selectOne(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug
               FROM users u INNER JOIN roles r ON r.id = u.role_id
              WHERE u.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        return (int) Database::scalar($sql, $params) > 0;
    }

    /** ¿Es el último administrador activo del sistema? (no se puede borrar) */
    public function isLastAdmin(int $userId): bool
    {
        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id
              WHERE r.slug = \'admin\' AND u.active = 1 AND u.id <> :id',
            ['id' => $userId]
        );
        return $count === 0;
    }

    /** @return array<int,array<string,mixed>> */
    public function operators(): array
    {
        return Database::select(
            'SELECT u.id, u.name FROM users u INNER JOIN roles r ON r.id = u.role_id
              WHERE u.active = 1 AND r.slug <> \'cliente\' ORDER BY u.name ASC'
        );
    }
}
