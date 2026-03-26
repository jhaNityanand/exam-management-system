<?php

namespace App\Http\Controllers\OrgAdmin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 403);
        $stats = $this->dashboardService->orgAdminStats($orgId);

        return view('org-admin.dashboard', compact('stats'));
    }
}
