<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService)
    {
    }

    /**
     * Display the Super-Admin dashboard.
     */
    public function index(Request $request): View
    {
        $stats = $this->dashboardService->adminStats();

        return view('admin.dashboard', compact('stats'));
    }
}
