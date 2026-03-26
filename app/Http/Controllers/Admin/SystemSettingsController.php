<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = \App\Models\SystemSetting::pluck('value', 'key')->toArray();

        // Default colors
        $roleColors = [
            'admin' => $settings['role_color_admin'] ?? '#111827',
            'org_admin' => $settings['role_color_org_admin'] ?? '#1e3a8a',
            'editor' => $settings['role_color_editor'] ?? '#064e3b',
            'viewer' => $settings['role_color_viewer'] ?? '#4c1d95',
        ];

        return view('admin.settings.edit', compact('roleColors', 'settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->has('action') && $request->string('action')->toString() === 'clear-cache') {
            Artisan::call('optimize:clear');
            return redirect()->route('admin.settings.edit')->with('success', 'Application cache cleared.');
        }

        $data = $request->validate([
            'role_color_admin' => ['nullable', 'string', 'max:7'],
            'role_color_org_admin' => ['nullable', 'string', 'max:7'],
            'role_color_editor' => ['nullable', 'string', 'max:7'],
            'role_color_viewer' => ['nullable', 'string', 'max:7'],
        ]);

        foreach ($data as $key => $value) {
            if ($value) {
                \App\Models\SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
                );
            }
        }

        cache()->forget('roleThemeColors');

        return redirect()->route('admin.settings.edit')->with('success', 'System settings updated.');
    }
}
