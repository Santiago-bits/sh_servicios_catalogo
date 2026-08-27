<?php
/**
 * ARCHIVO: app/controllers/admin/DashboardController.php
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Inquiry;
use App\Models\PriceHistory;
use App\Models\Quote;
use App\Models\SearchLog;
use App\Models\StockMovement;
use App\Services\AlertService;
use App\Services\StatsService;
use Core\Auth;

class DashboardController extends AdminController
{
    public function index(): void
    {
        $quoteModel = new Quote();
        $quoteModel->expireOverdue();   // marca vencidas las que corresponda

        $this->view('admin/dashboard/index', [
            'pageTitle'   => 'Dashboard · Panel',
            'adminTitle'  => 'Dashboard',
            'robots'      => 'noindex, nofollow',

            'stats'       => StatsService::dashboard(),
            'viewsChart'  => StatsService::viewsByDay(30),
            'quotesChart' => StatsService::quotesByMonth(12),
            'categoryChart'=> StatsService::productsByCategory('machine'),

            'topMachines' => StatsService::topProducts('machine', 5),
            'topParts'    => StatsService::topProducts('spare_part', 5),
            'topSearches' => (new SearchLog())->top(30, 8),

            'lowStock'    => (new StockMovement())->lowStock(6),
            'inquiries'   => (new Inquiry())->latest(6),
            'quotes'      => $quoteModel->search([], 1, 6)['data'],
            'quoteStats'  => $quoteModel->stats(),
            'expiring'    => $quoteModel->expiringSoon(7),

            'priceChanges'=> Auth::canSeeCost() ? (new PriceHistory())->latest(6) : [],
            'activity'    => Auth::can('audit.view') ? (new ActivityLog())->latest(8) : [],
            'alerts'      => AlertService::all(4),
        ]);
    }

    public function alerts(): void
    {
        $this->view('admin/dashboard/alerts', [
            'pageTitle'  => 'Alertas · Panel',
            'adminTitle' => 'Alertas del sistema',
            'robots'     => 'noindex, nofollow',
            'alerts'     => AlertService::all(10),
        ]);
    }
}
