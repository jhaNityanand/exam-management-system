<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\UpdateEmailSettingRequest;
use App\Services\Settings\EmailConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailSettingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected EmailConfigurationService $email,
    ) {}

    public function edit(): View
    {
        $orgId = $this->currentOrgId();
        $settings = $this->email->get($orgId);

        return view('backend.settings.email', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateEmailSettingRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $settings = $this->email->update($request->validated(), $orgId);

        return response()->json([
            'success' => true,
            'message' => 'Email configuration saved.',
            'settings' => $settings,
        ]);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email', 'max:190'],
        ]);

        $result = $this->email->sendTestEmail($data['to'], $this->currentOrgId());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }
}
