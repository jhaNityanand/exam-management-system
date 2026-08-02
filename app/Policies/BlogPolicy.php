<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;

class BlogPolicy
{
    use AuthorizesOrganizationResources;

    public function viewAny(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function view(User $user, Blog $blog): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($blog);
    }

    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function update(User $user, Blog $blog): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($blog);
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($blog);
    }

    public function restore(User $user, Blog $blog): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($blog);
    }

    public function forceDelete(User $user, Blog $blog): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($blog);
    }
}
