<?php

namespace App\Http\Controllers\Concerns;

/**
 * Resolves the active organization for backend controllers.
 * Prefer this over duplicating UserOrganization lookups.
 */
trait ResolvesCurrentOrganization
{
    protected function currentOrgId(): int
    {
        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }
}
