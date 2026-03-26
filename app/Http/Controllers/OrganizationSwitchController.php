<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $user = $request->user();
        if ($user->hasRole('admin')) {
            session([config('organization.session_key') => (int) $data['organization_id']]);

            return redirect()->back();
        }

        abort_unless($user->belongsToOrganization((int) $data['organization_id']), 403);

        session([config('organization.session_key') => (int) $data['organization_id']]);

        $role = $user->pivotRoleForOrganization((int) $data['organization_id']);

        return redirect()->to($this->dashboardForPivotRole($role));
    }

    protected function dashboardForPivotRole(?string $role): string
    {
        return match ($role) {
            'org_admin' => route('org-admin.dashboard', absolute: false),
            'editor' => route('editor.dashboard', absolute: false),
            'viewer' => route('viewer.dashboard', absolute: false),
            default => route('no-organization', absolute: false),
        };
    }
}
