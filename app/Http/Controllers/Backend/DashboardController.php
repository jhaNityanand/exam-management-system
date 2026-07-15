<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(protected DashboardService $dashboardService) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();
        $stats = $this->dashboardService->workspaceStats($orgId);

        return view('backend.dashboard', compact('stats'));
    }
}
