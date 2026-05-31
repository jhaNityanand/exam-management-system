<?php

use App\Models\Organization;

if (! function_exists('current_organization_id')) {
    /**
     * Return the active organization ID.
     *
     * SINGLE-ORG MODE (current):
     *   Always returns the first/only organization in the database.
     *   No session, no membership check — bypassed for single-org development.
     *
     * MULTI-ORG MODE (future — uncomment the block below and remove the single-org block):
     *   Read from session, validate the authenticated user belongs to that org.
     *
     *   use Illuminate\Support\Facades\Auth;
     *   $id = session(config('organization.session_key'));
     *   if (! $id) { return null; }
     *   if (Auth::check() && ! Auth::user()->belongsToOrganization((int) $id)) { return null; }
     *   return (int) $id;
     */
    function current_organization_id(): ?int
    {
        // ── SINGLE-ORG MODE ───────────────────────────────────────────────────
        static $cachedId = null;

        if ($cachedId === null) {
            $cachedId = Organization::value('id'); // first org, or null if table is empty
        }

        return $cachedId ? (int) $cachedId : null;
        // ─────────────────────────────────────────────────────────────────────
    }
}
