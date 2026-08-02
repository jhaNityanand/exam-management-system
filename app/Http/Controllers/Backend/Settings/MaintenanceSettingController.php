<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\UpdateMaintenanceSettingRequest;
use App\Services\Settings\MaintenanceModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MaintenanceSettingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected MaintenanceModeService $maintenance,
    ) {}

    public function edit(): View
    {
        return view('backend.settings.maintenance', [
            'settings' => $this->maintenance->get($this->currentOrgId()),
        ]);
    }

    public function update(UpdateMaintenanceSettingRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $settings = $this->maintenance->update($request->validated(), $orgId);

        return response()->json([
            'success' => true,
            'message' => $settings['enabled']
                ? 'Maintenance mode is now enabled. Visitors will see the maintenance page.'
                : 'Maintenance mode settings saved. The site is publicly accessible.',
            'settings' => $settings,
        ]);
    }
}
