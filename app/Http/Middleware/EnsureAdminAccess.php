<?php

namespace App\Http\Middleware;

use App\Http\Responses\PermissionDeniedResponse;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts /admin routes to users with an admin-panel organization role.
 *
 * Unauthorized authenticated users (e.g. candidates) receive a friendly
 * permission page at the same URL — they are not redirected away.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return PermissionDeniedResponse::toResponse(
                $request,
                'You do not have permission to access this page. Please sign in with an authorized account.'
            );
        }

        $membership = UserOrganization::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if (! $membership || ! OrganizationRoles::canAccessAdminPanel($membership->role)) {
            return PermissionDeniedResponse::toResponse($request);
        }

        return $next($request);
    }
}
