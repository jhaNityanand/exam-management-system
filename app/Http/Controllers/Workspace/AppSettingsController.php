<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\UserAppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $settings = UserAppSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['theme' => 'system', 'sidebar_collapsed' => false]
        );
        $profile = $user->profile ?? Profile::make(['id' => $user->id]);
        $organizations = $user->organizations()->orderBy('name')->get();

        return view('workspace.settings.edit', compact('settings', 'profile', 'organizations'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'theme'                   => ['required', Rule::in(['light', 'dark', 'system'])],
            'sidebar_collapsed'       => ['sometimes', 'boolean'],
            'default_organization_id' => [
                'nullable',
                'integer',
                Rule::exists('organizations', 'id'),
            ],
        ]);

        if (
            isset($data['default_organization_id'])
            && ! $user->belongsToOrganization((int) $data['default_organization_id'])
        ) {
            return back()
                ->withErrors(['default_organization_id' => 'You do not belong to that organization.'])
                ->withInput();
        }

        UserAppSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'theme'             => $data['theme'],
                'sidebar_collapsed' => $request->boolean('sidebar_collapsed'),
            ]
        );

        Profile::updateOrCreate(
            ['id' => $user->id],
            [
                'id'                      => $user->id,
                'status'                  => 'active',
                'default_organization_id' => $data['default_organization_id'] ?? null,
            ]
        );

        return redirect()->route('workspace.settings.edit')
            ->with('success', 'Settings saved.');
    }
}
