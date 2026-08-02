<?php

namespace App\Policies;

use App\Models\News;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;

class NewsPolicy
{
    use AuthorizesOrganizationResources;

    public function viewAny(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function view(User $user, News $news): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($news);
    }

    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function update(User $user, News $news): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($news);
    }

    public function delete(User $user, News $news): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($news);
    }

    public function restore(User $user, News $news): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($news);
    }

    public function forceDelete(User $user, News $news): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($news);
    }
}
