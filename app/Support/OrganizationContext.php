<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;

class OrganizationContext
{
    public function __construct(
        protected ?int $organizationId = null
    ) {}

    public function id(): ?int
    {
        return $this->organizationId;
    }

    public function organization(): ?Organization
    {
        if ($this->organizationId === null) {
            return null;
        }

        return Organization::find($this->organizationId);
    }

    public function pivotRole(User $user): ?string
    {
        if ($this->organizationId === null) {
            return null;
        }

        $pivot = $user->organizations()
            ->where('organizations.id', $this->organizationId)
            ->first()?->pivot;

        return $pivot?->role;
    }

    public function userCan(User $user, string $permission): bool
    {
        // Temporary development mode:
        // role and permission restrictions are intentionally disabled so
        // any authenticated user can access all modules during the UI rebuild.
        return (bool) $user;
    }

    public function set(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }
}
