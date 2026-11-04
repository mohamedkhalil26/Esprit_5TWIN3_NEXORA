<?php

namespace App\Http\Controllers;

use App\Services\Ai\Dashboard\AIDashboardService;
use App\Services\Ai\HubCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, AIDashboardService $dashboard, HubCatalog $hubCatalog): View
    {
        return view('front.home', [
            'aiKpis' => $request->user() ? $dashboard->front($request->user()) : null,
            'aiModules' => $hubCatalog->modules(),
        ]);
    }
}
