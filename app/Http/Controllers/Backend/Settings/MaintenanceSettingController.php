<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\UpdateMaintenanceSettingRequest;
use App\Models\Gallery;
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
        $orgId = $this->currentOrgId();
        $settings = $this->maintenance->get($orgId);

        $logoPreview = null;
        if (! empty($settings['logo_gallery_id'])) {
            $logoPreview = Gallery::query()->find($settings['logo_gallery_id'])?->file_url;
        }

        $backgroundPreview = null;
        if (! empty($settings['background_gallery_id'])) {
            $backgroundPreview = Gallery::query()->find($settings['background_gallery_id'])?->file_url;
        }

        return view('backend.settings.maintenance', [
            'settings' => $settings,
            'logoPreview' => $logoPreview,
            'backgroundPreview' => $backgroundPreview,
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
