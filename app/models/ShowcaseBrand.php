<?php
/**
 * ARCHIVO: app/models/ShowcaseBrand.php
 * ---------------------------------------------------------------------
 * "Marcas en la web": las marcas que se muestran en la home (Equipos y
 * repuestos / Neumáticos) y los logos de clientes del servicio de
 * Alquiler. Son independientes de las marcas de los productos: se
 * muestran u ocultan a gusto desde el panel.
 */

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

class ShowcaseBrand extends Model
{
    protected string $table = 'showcase_brands';

    protected array $fillable = ['section', 'name', 'logo', 'sort_order', 'active'];

    protected array $sortable = ['id', 'name', 'sort_order', 'section'];

    /** Secciones disponibles => título público. */
    public const SECTIONS = [
        'equipos'    => 'Equipos y repuestos',
        'neumaticos' => 'Neumáticos',
        'clientes'   => 'Empresas que confían en nosotros',
    ];

    /** @return array<int,array<string,mixed>> */
    public function activeBySection(string $section): array
    {
        return $this->safe(static fn (): array => Database::select(
            'SELECT * FROM showcase_brands WHERE section = :s AND active = 1 ORDER BY sort_order ASC, id ASC',
            ['s' => $section]
        ));
    }

    /**
     * Grupos para la home (sólo los que tienen marcas activas).
     * @return array<int,array{key:string,label:string,items:array<int,array<string,mixed>>}>
     */
    public function homeGroups(): array
    {
        $groups = [];
        foreach (['equipos', 'neumaticos'] as $key) {
            $items = $this->activeBySection($key);
            if ($items !== []) {
                $groups[] = ['key' => $key, 'label' => self::SECTIONS[$key], 'items' => $items];
            }
        }
        return $groups;
    }

    /** Todas, agrupadas por sección (panel). @return array<string,array<int,array<string,mixed>>> */
    public function grouped(): array
    {
        $out = array_fill_keys(array_keys(self::SECTIONS), []);
        $rows = $this->safe(static fn (): array => Database::select(
            'SELECT * FROM showcase_brands ORDER BY sort_order ASC, id ASC'
        ));
        foreach ($rows as $row) {
            $out[$row['section']][] = $row;
        }
        return $out;
    }

    public function nextSortOrder(string $section): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM showcase_brands WHERE section = :s',
            ['s' => $section]
        );
    }

    /**
     * Si todavía no se corrió la migración, la web no se rompe: la
     * sección de marcas simplemente no aparece.
     *
     * @param callable():array<int,array<string,mixed>> $query
     * @return array<int,array<string,mixed>>
     */
    private function safe(callable $query): array
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
