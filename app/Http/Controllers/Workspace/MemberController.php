<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        return view('workspace.members.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) session(config('organization.session_key'));
        abort_if($orgId === 0, 403, 'No organization context.');

        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();
        abort_if($user->id === $request->user()->id, 422, 'Use another account to change your own membership.');

        $org = Organization::findOrFail($orgId);
        if ($user->belongsToOrganization($orgId)) {
            // already a member — no-op
        } else {
            $org->users()->attach($user->id, ['status' => 'active']);
        }

        return redirect()->route('org-admin.members.index')
            ->with('success', 'Member added to organization.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403);

        $orgId = (int) session(config('organization.session_key'));
        Organization::findOrFail($orgId)->users()->detach($user->id);

        return redirect()->route('org-admin.members.index')
            ->with('success', 'Member removed from organization.');
    }
}
