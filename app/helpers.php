<?php

use App\Support\OrganizationContext;

if (! function_exists('org_context')) {
    function org_context(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }
}

if (! function_exists('current_organization_id')) {
    function current_organization_id(): ?int
    {
        return org_context()->id();
    }
}
