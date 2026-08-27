<?php
/**
 * ARCHIVO: app/controllers/SearchController.php
 * ---------------------------------------------------------------------
 * Buscador global: máquinas, repuestos, códigos OEM, marcas, categorías
 * y compatibilidad por modelo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SearchService;
use App\Services\SettingService;
use Core\Controller;
use Core\Request;

class SearchController extends Controller
{
    public function index(): void
    {
        $term    = (string) Request::get('q', '');
        $results = SearchService::global($term, 12);

        $this->view('pages/search', [
            'pageTitle'       => ($term !== '' ? 'Resultados para "' . $term . '"' : 'Buscador') . ' · ' . SettingService::companyName(),
            'metaDescription' => 'Buscá máquinas y repuestos por nombre, código interno, código OEM o modelo compatible.',
            'bodyClass'       => 'page-search',
            'robots'          => 'noindex, follow',
            'term'            => $term,
            'results'         => $results,
        ]);
    }

    /** Autocompletado del navbar. */
    public function suggest(): void
    {
        $term = (string) Request::get('q', '');

        $this->json([
            'ok'          => true,
            'term'        => $term,
            'suggestions' => SearchService::suggest($term, 8),
            'search_url'  => url('buscar?q=' . urlencode($term)),
        ]);
    }
}
