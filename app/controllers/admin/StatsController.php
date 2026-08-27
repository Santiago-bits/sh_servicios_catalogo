<?php
/**
 * ARCHIVO: app/controllers/admin/StatsController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SearchLog;
use App\Services\StatsService;
use Core\Request;

class StatsController extends AdminController
{
    public function index(): void
    {
        $days      = max(7, min(365, Request::int('dias', 30)));
        $searchLog = new SearchLog();

        $this->view('admin/stats/index', [
            'pageTitle'       => 'Estadísticas · Panel',
            'adminTitle'      => 'Estadísticas',
            'robots'          => 'noindex, nofollow',
            'days'            => $days,
            'stats'           => StatsService::dashboard(),
            'viewsChart'      => StatsService::viewsByDay($days),
            'quotesChart'     => StatsService::quotesByMonth(12),
            'channelChart'    => StatsService::inquiriesByChannel(),
            'machineCatChart' => StatsService::productsByCategory('machine'),
            'partCatChart'    => StatsService::productsByCategory('spare_part'),
            'topMachines'     => StatsService::topProducts('machine', 12),
            'topParts'        => StatsService::topProducts('spare_part', 12),
            'topCategories'   => StatsService::topCategories(10),
            'topSearches'     => $searchLog->top($days, 15),
            'topPartSearches' => $searchLog->top($days, 15, 'spare_part'),
            'noResults'       => $searchLog->withoutResults($days, 12),
        ]);
    }

    /** Datos de los gráficos en JSON (para refrescar sin recargar). */
    public function data(): void
    {
        $days = max(7, min(365, Request::int('dias', 30)));

        $this->json([
            'ok'       => true,
            'views'    => StatsService::viewsByDay($days),
            'quotes'   => StatsService::quotesByMonth(12),
            'channels' => StatsService::inquiriesByChannel(),
        ]);
    }
}
