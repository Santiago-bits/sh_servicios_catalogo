<?php
/**
 * ARCHIVO: app/controllers/RecommenderController.php
 * ---------------------------------------------------------------------
 * Asistente "¿Qué máquina necesitás?": con seis preguntas propone los
 * equipos del catálogo que mejor encajan y explica por qué.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Services\PriceService;
use App\Services\SettingService;
use Core\Controller;
use Core\Database;
use Core\Request;

class RecommenderController extends Controller
{
    public function index(): void
    {
        $this->view('pages/recommender', [
            'pageTitle'       => '¿Qué máquina necesitás? · ' . SettingService::companyName(),
            'metaDescription' => 'Respondé seis preguntas y te mostramos los equipos de nuestro catálogo que mejor se adaptan a tu operación.',
            'bodyClass'       => 'page-recommender',
            'answers'         => [],
            'results'         => null,
        ]);
    }

    public function result(): void
    {
        $answers = [
            'capacidad'   => Request::float('capacidad'),
            'altura'      => Request::float('altura'),
            'uso'         => (string) Request::post('uso', 'interior'),
            'combustible' => (string) Request::post('combustible', ''),
            'horas'       => (string) Request::post('horas', ''),
            'presupuesto' => Request::float('presupuesto'),
        ];

        $conditions = ['p.type = \'machine\'', 'p.active = 1', 'p.deleted_at IS NULL', 'p.availability <> \'vendida\''];
        $params     = [];

        if ($answers['capacidad'] > 0) {
            // Se acepta desde la capacidad pedida (con 10% de tolerancia hacia abajo)
            $conditions[]         = 'COALESCE(m.capacity_kg, 0) >= :capacidad';
            $params['capacidad']  = $answers['capacidad'] * 0.9;
        }

        if ($answers['altura'] > 0) {
            $conditions[]      = 'COALESCE(m.lift_height_mm, 0) >= :altura';
            $params['altura']  = $answers['altura'] * 0.9;
        }

        // Uso interior → se priorizan los eléctricos (cero emisiones)
        if ($answers['uso'] === 'interior' && $answers['combustible'] === '') {
            $conditions[] = '(m.fuel = \'electrico\' OR m.fuel IS NULL OR m.fuel = \'manual\')';
        } elseif ($answers['combustible'] !== '') {
            $valid = ['electrico', 'diesel', 'nafta', 'gas', 'glp', 'hibrido', 'manual'];
            if (in_array($answers['combustible'], $valid, true)) {
                $conditions[]           = 'm.fuel = :combustible';
                $params['combustible']  = $answers['combustible'];
            }
        }

        if ($answers['presupuesto'] > 0) {
            $conditions[]           = '(p.final_price <= :presupuesto OR p.final_price = 0)';
            $params['presupuesto']  = $answers['presupuesto'] * 1.15; // 15% de margen
        }

        $sql = 'SELECT ' . Product::PUBLIC_COLUMNS . ',
                       b.name AS brand_name, c.name AS category_name, c.slug AS category_slug,
                       m.model, m.year, m.hours, m.fuel, m.capacity_kg, m.lift_height_mm,
                       m.condition_type, m.location, m.power_hp, m.voltage,
                       (SELECT COALESCE(pi.thumb_path, pi.path) FROM product_images pi
                         WHERE pi.product_id = p.id ORDER BY pi.is_main DESC LIMIT 1) AS thumb
                  FROM products p
                  LEFT JOIN brands b     ON b.id = p.brand_id
                  LEFT JOIN categories c ON c.id = p.category_id
                  INNER JOIN machines m  ON m.product_id = p.id
                 WHERE ' . implode(' AND ', $conditions) . '
                 ORDER BY p.featured DESC, m.capacity_kg ASC, p.final_price ASC
                 LIMIT 6';

        $results = Database::select($sql, $params);

        // Explicación de por qué encaja cada equipo
        foreach ($results as $i => $product) {
            $reasons = [];

            if ($answers['capacidad'] > 0 && (float) $product['capacity_kg'] >= $answers['capacidad']) {
                $reasons[] = 'Levanta ' . kg_to_human((float) $product['capacity_kg']) . ', cubre los ' . kg_to_human($answers['capacidad']) . ' que necesitás';
            }
            if ($answers['altura'] > 0 && (int) $product['lift_height_mm'] >= $answers['altura']) {
                $reasons[] = 'Eleva hasta ' . mm_to_human((int) $product['lift_height_mm']);
            }
            if ($answers['uso'] === 'interior' && $product['fuel'] === 'electrico') {
                $reasons[] = 'Eléctrico: sin emisiones, apto para trabajo en interior';
            }
            if ($answers['uso'] === 'exterior' && in_array($product['fuel'], ['diesel', 'gas', 'glp'], true)) {
                $reasons[] = 'Motor a ' . mb_strtolower(fuel_label((string) $product['fuel'])) . ', pensado para exterior';
            }
            if ($answers['presupuesto'] > 0 && (float) $product['final_price'] > 0 && (float) $product['final_price'] <= $answers['presupuesto']) {
                $reasons[] = 'Entra en tu presupuesto';
            }

            $results[$i]['reasons'] = $reasons;
        }

        $this->view('pages/recommender', [
            'pageTitle'       => 'Resultados del asistente · ' . SettingService::companyName(),
            'metaDescription' => 'Equipos recomendados según tu operación.',
            'bodyClass'       => 'page-recommender',
            'robots'          => 'noindex, follow',
            'answers'         => $answers,
            'results'         => $results,
        ]);
    }
}
