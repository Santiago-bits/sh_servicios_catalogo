<?php
/**
 * ARCHIVO: app/controllers/CompareController.php
 * ---------------------------------------------------------------------
 * Comparador de hasta 3 máquinas (configurable). Los IDs llegan por
 * query string; el navegador los mantiene en localStorage.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Feature;
use App\Models\Product;
use App\Services\PriceService;
use App\Services\SettingService;
use Core\Controller;
use Core\Request;

class CompareController extends Controller
{
    public function index(): void
    {
        $max = max(2, min(4, SettingService::int('compare_max', 3)));
        $ids = array_slice(array_filter(array_map('intval', Request::array('ids'))), 0, $max);

        if ($ids === [] && Request::get('ids') !== null) {
            $ids = array_slice(
                array_filter(array_map('intval', explode(',', (string) Request::get('ids')))),
                0,
                $max
            );
        }

        $productModel = new Product();
        $products     = $ids === [] ? [] : $productModel->findMany($ids);

        // Sólo se comparan máquinas
        $products = array_values(array_filter($products, static fn (array $p): bool => $p['type'] === 'machine'));

        $featureModel = new Feature();
        $rows         = [];

        if ($products !== []) {
            $values = [];
            foreach ($products as $product) {
                $values[(int) $product['id']] = $featureModel->valuesFor((int) $product['id']);
            }

            // Filas fijas (columnas propias de la tabla machines)
            $fixed = [
                ['label' => 'Precio',        'key' => '_price'],
                ['label' => 'Marca',         'key' => 'brand_name'],
                ['label' => 'Modelo',        'key' => 'model'],
                ['label' => 'Año',           'key' => 'year'],
                ['label' => 'Condición',     'key' => 'condition_type'],
                ['label' => 'Horas de uso',  'key' => 'hours',       'format' => 'number'],
                ['label' => 'Combustible',   'key' => 'fuel',        'format' => 'fuel'],
                ['label' => 'Capacidad',     'key' => 'capacity_kg', 'format' => 'kg'],
                ['label' => 'Altura máxima', 'key' => 'lift_height_mm', 'format' => 'mm'],
                ['label' => 'Altura replegada', 'key' => 'closed_height_mm', 'format' => 'mm'],
                ['label' => 'Peso',          'key' => 'weight_kg',   'format' => 'kg'],
                ['label' => 'Largo',         'key' => 'length_mm',   'format' => 'mm'],
                ['label' => 'Ancho',         'key' => 'width_mm',    'format' => 'mm'],
                ['label' => 'Radio de giro', 'key' => 'turn_radius_mm', 'format' => 'mm'],
                ['label' => 'Motor',         'key' => 'engine'],
                ['label' => 'Potencia',      'key' => 'power_hp',    'format' => 'hp'],
                ['label' => 'Transmisión',   'key' => 'transmission'],
                ['label' => 'Batería',       'key' => 'battery'],
                ['label' => 'Voltaje',       'key' => 'voltage'],
                ['label' => 'Tipo de mástil','key' => 'mast_type'],
                ['label' => 'Ubicación',     'key' => 'location'],
            ];

            foreach ($fixed as $row) {
                $cells   = [];
                $hasData = false;

                foreach ($products as $product) {
                    $value = $row['key'] === '_price'
                        ? (PriceService::isPublicPriceVisible($product)
                            ? money(PriceService::effectivePrice($product), (string) $product['currency'])
                            : 'Consultar')
                        : ($product[$row['key']] ?? null);

                    if ($row['key'] !== '_price' && $value !== null && $value !== '') {
                        $value = match ($row['format'] ?? '') {
                            'kg'     => kg_to_human((float) $value),
                            'mm'     => mm_to_human((int) $value),
                            'hp'     => number_es((float) $value, 0) . ' HP',
                            'number' => number_es((float) $value),
                            'fuel'   => fuel_label((string) $value),
                            default  => (string) $value,
                        };
                    }

                    if ($value !== null && $value !== '' && $value !== '—') {
                        $hasData = true;
                    }

                    $cells[] = $value !== null && $value !== '' ? (string) $value : '—';
                }

                if ($hasData) {
                    $rows[] = ['label' => $row['label'], 'cells' => $cells];
                }
            }

            // Filas dinámicas (características configurables)
            foreach ($featureModel->comparable('machine') as $feature) {
                $cells   = [];
                $hasData = false;

                foreach ($products as $product) {
                    $value = $values[(int) $product['id']][$feature['slug']]['value_text'] ?? null;
                    if ($value !== null && $value !== '') {
                        $hasData = true;
                        $value  .= $feature['unit'] !== null ? ' ' . $feature['unit'] : '';
                    }
                    $cells[] = $value ?: '—';
                }

                if ($hasData) {
                    $rows[] = ['label' => (string) $feature['name'], 'cells' => $cells, 'dynamic' => true];
                }
            }
        }

        $this->view('compare/index', [
            'pageTitle'       => 'Comparador de máquinas · ' . SettingService::companyName(),
            'metaDescription' => 'Compará hasta ' . $max . ' equipos lado a lado: capacidad, altura, motor, dimensiones y precio.',
            'bodyClass'       => 'page-compare',
            'robots'          => 'noindex, follow',
            'products'        => $products,
            'rows'            => $rows,
            'max'             => $max,
        ]);
    }

    /** Datos del comparador en JSON (para actualizar sin recargar). */
    public function data(): void
    {
        $max = max(2, min(4, SettingService::int('compare_max', 3)));
        $ids = array_slice(
            array_filter(array_map('intval', explode(',', (string) Request::get('ids', '')))),
            0,
            $max
        );

        $products = $ids === [] ? [] : (new Product())->findMany($ids);

        $this->json([
            'ok'       => true,
            'count'    => count($products),
            'max'      => $max,
            'products' => array_map(static function (array $p): array {
                $p = PriceService::publicView($p);
                return [
                    'id'    => (int) $p['id'],
                    'name'  => $p['name'],
                    'code'  => $p['code'],
                    'url'   => product_url($p),
                    'image' => upload_url($p['thumb'] ?? $p['image']),
                    'price' => PriceService::displayPrice($p),
                ];
            }, $products),
        ]);
    }
}
