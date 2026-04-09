<?php

if (! function_exists('current_organization_id')) {
    function current_organization_id(): ?int
    {
        $value = session(config('organization.session_key'));
        return $value ? (int) $value : null;
    }
}
