<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\UpdateIntegrationsSettingRequest;
use App\Services\Settings\IntegrationsSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class IntegrationsSettingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected IntegrationsSettingsService $integrations,
    ) {}

    public function edit(): View
    {
        return view('backend.settings.integrations', [
            'settings' => $this->integrations->get($this->currentOrgId()),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(UpdateIntegrationsSettingRequest $request): JsonResponse
    {
        $settings = $this->integrations->update($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Integrations & privacy settings saved.',
            'settings' => $settings,
        ]);
    }
}
