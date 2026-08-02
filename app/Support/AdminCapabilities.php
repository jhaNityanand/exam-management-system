<?php

namespace App\Support;

/**
 * Canonical admin-panel capabilities for admin / org_admin roles.
 *
 * Today both panel roles receive the full capability set (same access).
 * Capability constants and middleware remain so privileges can be split later
 * without rewriting controllers/routes.
 *
 * Editor / Viewer remain deferred; do not grant panel access via those strings.
 */
final class AdminCapabilities
{
    /** Content modules: questions, exams, blogs, news, gallery, candidates, ads. */
    public const CONTENT = 'content';

    /** Organization branding, members, FAQs, SEO for the current org. */
    public const ORGANIZATION = 'organization';

    /** Platform settings: cache, maintenance, email SMTP, integrations, security. */
    public const PLATFORM = 'platform';

    /**
     * Full panel access — shared by Application Admin and Organization Admin.
     * Future: narrow ORG_ADMIN by removing PLATFORM (or similar) from its list.
     *
     * @return list<string>
     */
    public static function allCapabilities(): array
    {
        return [
            self::CONTENT,
            self::ORGANIZATION,
            self::PLATFORM,
        ];
    }

    /**
     * @return list<string>
     */
    public static function forRole(?string $role): array
    {
        if (! OrganizationRoles::canAccessAdminPanel($role)) {
            return [];
        }

        // admin and org_admin are equivalent until a future privilege split.
        return self::allCapabilities();
    }

    public static function roleCan(?string $role, string $capability): bool
    {
        return in_array($capability, self::forRole($role), true);
    }

    public static function userCan(?\App\Models\User $user, string $capability): bool
    {
        if (! $user) {
            return false;
        }

        return self::roleCan($user->activeOrganizationRole(), $capability);
    }
}
