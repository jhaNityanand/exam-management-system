<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the user is a global admin or has at least one organization membership.
 */
class EnsureAdminOrOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($user->organizations()->exists()) {
            return $next($request);
        }

        return redirect()->route('no-organization');
    }
}
