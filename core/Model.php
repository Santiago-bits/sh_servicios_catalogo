<?php
/**
 * ARCHIVO: core/Model.php
 * ---------------------------------------------------------------------
 * Modelo base: CRUD, paginación y un pequeño query builder para
 * armar filtros dinámicos SIN concatenar entrada del usuario.
 *
 * Las columnas y direcciones de ordenamiento se validan contra listas
 * blancas antes de llegar al SQL.
 */

declare(strict_types=1);

namespace Core;

abstract class Model
{
    protected string $table   = '';
    protected string $primaryKey = 'id';

    /** Columnas permitidas para INSERT/UPDATE masivos. @var array<int,string> */
    protected array $fillable = [];

    /** Columnas permitidas en ORDER BY. @var array<int,string> */
    protected array $sortable = ['id'];

    public function table(): string
    {
        return $this->table;
    }

    // ----------------------------------------------------------------
    // Lectura
    // ----------------------------------------------------------------

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        return Database::selectOne(
            sprintf('SELECT * FROM `%s` WHERE `%s` = :id LIMIT 1', $this->table, $this->primaryKey),
            ['id' => $id]
        );
    }

    /** @return array<string,mixed>|null */
    public function findBy(string $column, mixed $value): ?array
    {
        $column = $this->safeColumn($column);
        return Database::selectOne(
            sprintf('SELECT * FROM `%s` WHERE `%s` = :value LIMIT 1', $this->table, $column),
            ['value' => $value]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $orderBy   = $this->safeSort($orderBy);
        $direction = $this->safeDirection($direction);

        return Database::select(
            sprintf('SELECT * FROM `%s` ORDER BY `%s` %s', $this->table, $orderBy, $direction)
        );
    }

    /**
     * @param array<string,mixed> $conditions columna => valor (igualdad)
     * @return array<int,array<string,mixed>>
     */
    public function where(array $conditions, string $orderBy = 'id', string $direction = 'ASC', ?int $limit = null): array
    {
        [$whereSql, $params] = $this->buildWhere($conditions);

        $sql = sprintf(
            'SELECT * FROM `%s` %s ORDER BY `%s` %s',
            $this->table,
            $whereSql,
            $this->safeSort($orderBy),
            $this->safeDirection($direction)
        );

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        return Database::select($sql, $params);
    }

    /** @param array<string,mixed> $conditions */
    public function count(array $conditions = []): int
    {
        [$whereSql, $params] = $this->buildWhere($conditions);
        return (int) Database::scalar(
            sprintf('SELECT COUNT(*) FROM `%s` %s', $this->table, $whereSql),
            $params
        );
    }

    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    // ----------------------------------------------------------------
    // Escritura
    // ----------------------------------------------------------------

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        return Database::insert($this->table, $this->filterFillable($data));
    }

    /** @param array<string,mixed> $data */
    public function updateById(int $id, array $data): int
    {
        $data = $this->filterFillable($data);
        if ($data === []) {
            return 0;
        }
        return Database::update($this->table, $data, sprintf('`%s` = :pk_id', $this->primaryKey), ['pk_id' => $id]);
    }

    public function deleteById(int $id): int
    {
        return Database::delete($this->table, sprintf('`%s` = :id', $this->primaryKey), ['id' => $id]);
    }

    // ----------------------------------------------------------------
    // Paginación
    // ----------------------------------------------------------------

    /**
     * @param array<string|int,mixed> $params
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,per_page:int,last_page:int,from:int,to:int}
     */
    public static function paginateRaw(string $baseSql, string $countSql, array $params, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $total   = (int) Database::scalar($countSql, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page    = min($page, $lastPage);
        $offset  = ($page - 1) * $perPage;

        // LIMIT/OFFSET van como enteros ya saneados, nunca como texto del usuario.
        $sql  = $baseSql . sprintf(' LIMIT %d OFFSET %d', $perPage, $offset);
        $data = Database::select($sql, $params);

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
            'from'      => $total === 0 ? 0 : $offset + 1,
            'to'        => min($offset + $perPage, $total),
        ];
    }

    /**
     * @param array<string,mixed> $conditions
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,per_page:int,last_page:int,from:int,to:int}
     */
    public function paginate(int $page = 1, int $perPage = 20, array $conditions = [], string $orderBy = 'id', string $direction = 'DESC'): array
    {
        [$whereSql, $params] = $this->buildWhere($conditions);

        $base  = sprintf(
            'SELECT * FROM `%s` %s ORDER BY `%s` %s',
            $this->table,
            $whereSql,
            $this->safeSort($orderBy),
            $this->safeDirection($direction)
        );
        $count = sprintf('SELECT COUNT(*) FROM `%s` %s', $this->table, $whereSql);

        return self::paginateRaw($base, $count, $params, $page, $perPage);
    }

    // ----------------------------------------------------------------
    // Utilidades internas
    // ----------------------------------------------------------------

    /**
     * @param array<string,mixed> $conditions
     * @return array{0:string,1:array<string,mixed>}
     */
    protected function buildWhere(array $conditions): array
    {
        if ($conditions === []) {
            return ['', []];
        }

        $parts  = [];
        $params = [];
        $i      = 0;

        foreach ($conditions as $column => $value) {
            $safe = $this->safeColumn($column);
            $key  = 'w' . $i++;

            if ($value === null) {
                $parts[] = sprintf('`%s` IS NULL', $safe);
                continue;
            }
            if (is_array($value)) {
                $in = [];
                foreach (array_values($value) as $j => $item) {
                    $ph        = $key . '_' . $j;
                    $in[]      = ':' . $ph;
                    $params[$ph] = $item;
                }
                $parts[] = $in === []
                    ? '1 = 0'
                    : sprintf('`%s` IN (%s)', $safe, implode(', ', $in));
                continue;
            }

            $parts[]      = sprintf('`%s` = :%s', $safe, $key);
            $params[$key] = $value;
        }

        return ['WHERE ' . implode(' AND ', $parts), $params];
    }

    /** Sólo se aceptan nombres de columna con caracteres seguros. */
    protected function safeColumn(string $column): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException('Nombre de columna inválido.');
        }
        return $column;
    }

    protected function safeSort(string $column): string
    {
        return in_array($column, $this->sortable, true) ? $column : $this->primaryKey;
    }

    protected function safeDirection(string $direction): string
    {
        return strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    protected function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /** Genera un slug único dentro de la tabla. */
    public function uniqueSlug(string $text, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $column = $this->safeColumn($column);
        $base   = slugify($text);
        if ($base === '') {
            $base = 'item';
        }
        $slug = $base;
        $i    = 2;

        while (true) {
            $sql    = sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = :slug', $this->table, $column);
            $params = ['slug' => $slug];

            if ($ignoreId !== null) {
                $sql .= sprintf(' AND `%s` <> :ignore', $this->primaryKey);
                $params['ignore'] = $ignoreId;
            }

            if ((int) Database::scalar($sql, $params) === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
