<?php

namespace App\Http\Middleware;

use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOrganizationMembership
{
    public function __construct(protected OrganizationContext $context)
    {
    }

    /**
     * @param  string  ...$roles  Pivot roles allowed (pipe-separated in single arg supported)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode('|', $r) as $part) {
                $allowed[] = trim($part);
            }
        }
        $allowed = array_unique(array_filter($allowed));

        $orgId = $this->context->id();
        if ($orgId === null || !$user->belongsToOrganization($orgId)) {
            return redirect()->route('no-organization')
                ->with('error', 'Select an organization you belong to.');
        }

        $pivotRole = $this->context->pivotRole($user);
        if ($pivotRole === null || !in_array($pivotRole, $allowed, true)) {
            abort(403, 'You do not have access to this area for the selected organization.');
        }

        return $next($request);
    }
}
