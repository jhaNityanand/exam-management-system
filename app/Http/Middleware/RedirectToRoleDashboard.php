<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After login, redirect users to their role-specific dashboard.
 * Register this as the 'redirect.role' alias if you want to use it
 * independently; alternatively, the /dashboard route handles this
 * redirect transparently.
 */
class RedirectToRoleDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only act on auth'd users hitting /dashboard
        if ($request->user() && $request->routeIs('dashboard')) {
            $user = $request->user();

            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('org_admin')) {
                return redirect()->route('org-admin.dashboard');
            }

            if ($user->hasRole('editor')) {
                return redirect()->route('editor.dashboard');
            }

            return redirect()->route('viewer.dashboard');
        }

        return $response;
    }
}
