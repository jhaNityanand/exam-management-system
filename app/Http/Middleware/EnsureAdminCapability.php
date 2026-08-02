<?php

namespace App\Http\Middleware;

use App\Http\Responses\PermissionDeniedResponse;
use App\Support\AdminCapabilities;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires an authenticated admin-panel user with a named capability
 * (content | organization | platform).
 */
class EnsureAdminCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (! $user || ! AdminCapabilities::userCan($user, $capability)) {
            return PermissionDeniedResponse::toResponse(
                $request,
                'You do not have permission to perform this action.'
            );
        }

        return $next($request);
    }
}
