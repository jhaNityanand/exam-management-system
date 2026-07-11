<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\UserOrganization;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();
        $stats = $this->dashboardService->workspaceStats($orgId);

        return view('backend.dashboard', compact('stats'));
    }

    protected function currentOrgId(): int
    {
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            if ($orgId) {
                return (int) $orgId;
            }
        }

        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }
}
