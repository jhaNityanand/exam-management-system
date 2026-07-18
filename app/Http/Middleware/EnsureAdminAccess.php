<?php

namespace App\Http\Middleware;

use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $membership = UserOrganization::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $membership || ! OrganizationRoles::canAccessAdminPanel($membership->role)) {
            return redirect()
                ->route('frontend.account.dashboard')
                ->with('error', 'You do not have access to the admin panel.');
        }

        return $next($request);
    }
}
