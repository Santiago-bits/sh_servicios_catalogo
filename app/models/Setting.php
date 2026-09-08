<?php
/**
 * ARCHIVO: app/models/Setting.php
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class Setting extends Model
{
    protected string $table = 'site_settings';
    protected array $fillable = ['group_name', 'key_name', 'value', 'type', 'options', 'label', 'help', 'sort_order'];

    /** @return array<string,string> */
    public function pairs(): array
    {
        $rows = Database::select('SELECT key_name, value FROM site_settings');
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['key_name']] = (string) ($row['value'] ?? '');
        }
        return $out;
    }

    /** @return array<string,array<int,array<string,mixed>>> */
    public function grouped(): array
    {
        $rows    = Database::select('SELECT * FROM site_settings ORDER BY group_name ASC, sort_order ASC');
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group_name']][] = $row;
        }
        return $grouped;
    }

    public function put(string $key, string $value): void
    {
        Database::execute(
            'UPDATE site_settings SET value = :value WHERE key_name = :key',
            ['value' => $value, 'key' => $key]
        );
    }

    /**
     * Guarda un ajuste creándolo si no existe (para claves que no vienen
     * en el seed inicial, como las novedades del panel).
     */
    public function upsert(string $key, string $value, string $group = 'sistema', string $type = 'textarea', string $label = ''): void
    {
        Database::execute(
            'INSERT INTO site_settings (group_name, key_name, value, type, label)
                  VALUES (:g, :k, :v, :t, :l)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            ['g' => $group, 'k' => $key, 'v' => $value, 't' => $type, 'l' => $label !== '' ? $label : $key]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function currencies(): array
    {
        return Database::select('SELECT * FROM currencies ORDER BY is_base DESC, code ASC');
    }

    public function updateRate(string $code, float $rate): void
    {
        Database::execute(
            'UPDATE currencies SET rate_to_base = :rate WHERE code = :code',
            ['rate' => $rate, 'code' => $code]
        );
    }
}
