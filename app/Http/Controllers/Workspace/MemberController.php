<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Concerns\InteractsWithOrganization;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    use InteractsWithOrganization;

    public function index(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('member.view'), 403);

        return view('workspace.members.index', ['panelLayout' => 'layouts.org-admin']);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('member.manage'), 403);

        $orgId = $this->currentOrgId();

        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(['org_admin', 'editor', 'viewer'])],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();
        abort_if($user->id === $request->user()->id, 422, 'Use another admin to change your own membership.');

        $org = Organization::findOrFail($orgId);
        if ($user->belongsToOrganization($orgId)) {
            $org->users()->updateExistingPivot($user->id, ['role' => $data['role']]);
        } else {
            $org->users()->attach($user->id, ['role' => $data['role'], 'status' => 'active']);
        }

        return redirect()->route('org-admin.members.index')
            ->with('success', 'Member saved for this organization.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('member.manage'), 403);
        abort_if($user->id === $request->user()->id, 403);

        Organization::findOrFail($this->currentOrgId())->users()->detach($user->id);

        return redirect()->route('org-admin.members.index')
            ->with('success', 'Member removed from organization.');
    }
}
