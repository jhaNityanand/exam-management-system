<?php

namespace App\Policies;

use App\Models\Gallery;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;

class GalleryPolicy
{
    use AuthorizesOrganizationResources;

    public function viewAny(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function view(User $user, Gallery $gallery): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($gallery);
    }

    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function update(User $user, Gallery $gallery): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($gallery);
    }

    public function delete(User $user, Gallery $gallery): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($gallery);
    }

    public function restore(User $user, Gallery $gallery): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($gallery);
    }

    public function forceDelete(User $user, Gallery $gallery): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($gallery);
    }
}
