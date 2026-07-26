<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\UpdateSecuritySettingRequest;
use App\Services\Settings\SecuritySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SecuritySettingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected SecuritySettingsService $security,
    ) {}

    public function edit(): View
    {
        return view('backend.settings.security', [
            'settings' => $this->security->get($this->currentOrgId()),
        ]);
    }

    public function update(UpdateSecuritySettingRequest $request): JsonResponse
    {
        $settings = $this->security->update($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Security settings saved.',
            'settings' => $settings,
        ]);
    }
}
