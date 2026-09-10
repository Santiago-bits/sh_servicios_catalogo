<?php
/**
 * ARCHIVO: core/Database.php
 * ---------------------------------------------------------------------
 * Conexión PDO única (singleton) y helpers de consulta.
 * TODAS las consultas del sistema pasan por acá usando sentencias
 * preparadas: nunca se concatena entrada del usuario en el SQL.
 */

declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;
    private static int $queryCount = 0;
    /** @var array<int,array{sql:string,time:float}> */
    private static array $log = [];

    private function __construct() {}

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        /** @var array<string,mixed> $config */
        $config = require CONFIG_PATH . '/database.php';

        // El charset NO va en el DSN a propósito: algunos hostings tienen una
        // versión del cliente MySQL que rechaza  charset=utf8mb4  ahí y tiran
        // SQLSTATE[HY000] [2019] Unknown character set. Se fija después de
        // conectar con SET NAMES, que funciona en todos lados.
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database']
        );

        $charset = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($config['charset'] ?? 'utf8mb4')) ?: 'utf8mb4';

        try {
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            self::$pdo->exec("SET NAMES '{$charset}'");
            self::$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");

            // La zona horaria de la sesión MySQL se alinea con la de PHP
            // (APP_TIMEZONE). Así NOW() / CURRENT_TIMESTAMP y las columnas
            // created_at / updated_at guardan la hora local aunque el servidor
            // esté en UTC (es el caso del hosting). Se usa el desfasaje numérico
            // (-03:00), que no necesita las tablas de zonas horarias de MySQL.
            $tzOffset = (new \DateTime('now'))->format('P');
            if (preg_match('/^[+-]\d{2}:\d{2}$/', $tzOffset) === 1) {
                self::$pdo->exec("SET time_zone = '{$tzOffset}'");
            }
        } catch (PDOException $e) {
            error_log('[DB] ' . $e->getMessage());

            if (APP_DEBUG) {
                throw new RuntimeException(
                    'No se pudo conectar a la base de datos: ' . $e->getMessage() .
                    ' — Revisá config/database.php y el archivo .env',
                    0,
                    $e
                );
            }

            http_response_code(503);
            exit('Servicio no disponible. Intentá nuevamente en unos minutos.');
        }

        return self::$pdo;
    }

    /**
     * Ejecuta una consulta preparada.
     *
     * @param array<string|int,mixed> $params
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = self::connection()->prepare($sql);

            foreach ($params as $key => $value) {
                $param = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? $key : ':' . $key);
                $type  = match (true) {
                    is_int($value)  => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default         => PDO::PARAM_STR,
                };
                $stmt->bindValue($param, $value, $type);
            }

            $stmt->execute();
        } catch (PDOException $e) {
            error_log('[SQL] ' . $e->getMessage() . ' | ' . $sql);
            if (APP_DEBUG) {
                throw new RuntimeException('Error SQL: ' . $e->getMessage() . "\n\nConsulta: " . $sql, 0, $e);
            }
            throw new RuntimeException('Ocurrió un error al acceder a los datos.', 0, $e);
        }

        self::$queryCount++;
        if (APP_DEBUG) {
            self::$log[] = ['sql' => $sql, 'time' => (microtime(true) - $start) * 1000];
        }

        return $stmt;
    }

    /** @param array<string|int,mixed> $params @return array<int,array<string,mixed>> */
    public static function select(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** @param array<string|int,mixed> $params @return array<string,mixed>|null */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string|int,mixed> $params */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** @param array<string|int,mixed> $params */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Verifica que tabla y columnas sean identificadores simples antes de
     * interpolarlos en el SQL. Defensa en profundidad: hoy los nombres
     * siempre son literales del código, nunca entrada del usuario.
     *
     * @param array<int,string> $columns
     */
    private static function assertIdentifiers(string $table, array $columns): void
    {
        $valid = static fn (string $id): bool => (bool) preg_match('/^[A-Za-z0-9_]+$/', $id);

        if (!$valid($table)) {
            throw new \InvalidArgumentException('Nombre de tabla inválido: ' . $table);
        }
        foreach ($columns as $column) {
            if (!is_string($column) || !$valid($column)) {
                throw new \InvalidArgumentException('Nombre de columna inválido: ' . var_export($column, true));
            }
        }
    }

    /** Inserta un registro y devuelve el ID generado. @param array<string,mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        self::assertIdentifiers($table, $columns);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, $data);

        return (int) self::connection()->lastInsertId();
    }

    /**
     * Actualiza registros filtrando por una condición preparada.
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $whereParams
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        self::assertIdentifiers($table, array_keys($data));

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = sprintf('`%s` = :set_%s', $column, $column);
        }

        $params = [];
        foreach ($data as $column => $value) {
            $params['set_' . $column] = $value;
        }
        foreach ($whereParams as $key => $value) {
            $params[$key] = $value;
        }

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), $where);

        return self::execute($sql, $params);
    }

    /** @param array<string,mixed> $params */
    public static function delete(string $table, string $where, array $params = []): int
    {
        self::assertIdentifiers($table, []);
        return self::execute(sprintf('DELETE FROM `%s` WHERE %s', $table, $where), $params);
    }

    // ----------------------------------------------------------------
    // Transacciones
    // ----------------------------------------------------------------

    public static function beginTransaction(): void
    {
        if (!self::connection()->inTransaction()) {
            self::connection()->beginTransaction();
        }
    }

    public static function commit(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->commit();
        }
    }

    public static function rollBack(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }

    /** Ejecuta un callback dentro de una transacción. */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }

    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    /** @return array<int,array{sql:string,time:float}> */
    public static function log(): array
    {
        return self::$log;
    }
}
