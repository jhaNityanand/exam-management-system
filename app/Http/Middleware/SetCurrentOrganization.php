<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Organization;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    public function __construct(protected OrganizationContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Temporary development mode:
        // keep a current organization context for every logged-in user,
        // including admins, so all modules remain reachable.

        $key = config('organization.session_key');
        $sessionOrgId = session($key);

        if ($sessionOrgId && $user->belongsToOrganization((int) $sessionOrgId)) {
            $this->context->set((int) $sessionOrgId);
        } else {
            $resolved = $this->resolveDefaultOrganizationId($user);
            $this->context->set($resolved);
            if ($resolved !== null) {
                session([$key => $resolved]);
            }
        }

        return $next($request);
    }

    protected function resolveDefaultOrganizationId(User $user): ?int
    {
        $profile = $user->profile;
        if ($profile?->default_organization_id && $user->belongsToOrganization((int) $profile->default_organization_id)) {
            return (int) $profile->default_organization_id;
        }

        $first = $user->organizations()->orderBy('organizations.name')->first();
        if ($first) {
            return $first->id;
        }

        return Organization::query()->orderBy('name')->value('id');
    }
}
