<?php

namespace App\Policies;

use App\Models\Cms\Advertisement;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;
use App\Support\AdminCapabilities;

class AdvertisementPolicy
{
    use AuthorizesOrganizationResources;

    public function viewAny(User $user): bool
    {
        return AdminCapabilities::userCan($user, AdminCapabilities::ORGANIZATION);
    }

    public function view(User $user, Advertisement $advertisement): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentOrganization($advertisement);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Advertisement $advertisement): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentOrganization($advertisement);
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentOrganization($advertisement);
    }
}
