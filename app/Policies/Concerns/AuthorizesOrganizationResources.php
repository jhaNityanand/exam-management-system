<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\AdminCapabilities;
use App\Support\OrganizationRoles;

trait AuthorizesOrganizationResources
{
    protected function isPanelUser(?User $user): bool
    {
        return $user !== null && OrganizationRoles::canAccessAdminPanel($user->activeOrganizationRole());
    }

    protected function canManageContent(?User $user): bool
    {
        return AdminCapabilities::userCan($user, AdminCapabilities::CONTENT);
    }

    protected function belongsToCurrentOrganization(object $model): bool
    {
        if (! property_exists($model, 'organization_id') && ! isset($model->organization_id)) {
            return false;
        }

        $orgId = current_organization_id();

        return $orgId !== null && (int) $model->organization_id === (int) $orgId;
    }
}
