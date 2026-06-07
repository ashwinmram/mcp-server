<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with knowledge base and project stats.
     */
    public function __invoke(DashboardStatsService $stats): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $stats->getStats(),
        ]);
    }
}
