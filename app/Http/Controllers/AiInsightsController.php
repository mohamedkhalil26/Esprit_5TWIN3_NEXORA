<?php

namespace App\Http\Controllers;

use App\Services\Ai\Dashboard\AIDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiInsightsController extends Controller
{
    public function index(Request $request, AIDashboardService $dashboard): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $months = (int) $request->integer('months', 6);
        if (! in_array($months, [3, 6, 12], true)) {
            $months = 6;
        }
        $insights = $dashboard->insights($months);
        $kpis = $dashboard->admin(now()->startOfMonth()->subMonths($months - 1), now());

        return view('admin.ai-insights.index', compact('insights', 'kpis', 'months'));
    }
}
