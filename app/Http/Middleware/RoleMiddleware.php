<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Supports single role OR pipe-separated roles: role:admin|editor
     */
    public function handle(Request $request, Closure $next, string...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        foreach ($roles as $role) {
            // Allow pipe-separated roles in a single argument: 'admin|editor'
            $roleParts = explode('|', $role);
            foreach ($roleParts as $part) {
                if ($request->user()->hasRole(trim($part))) {
                    return $next($request);
                }
            }
        }

        abort(403, 'Unauthorized. You do not have the required role to access this resource.');
        return response('Unauthorized', 403);
    }
}
