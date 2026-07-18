<?php

namespace App\Support;

final class OrganizationRoles
{
    public const ADMIN = 'admin';

    public const ORG_ADMIN = 'org_admin';

    public const EDITOR = 'editor';

    public const VIEWER = 'viewer';

    public const CANDIDATE = 'candidate';

    /**
     * @return list<string>
     */
    public static function adminPanelRoles(): array
    {
        return [self::ADMIN, self::ORG_ADMIN, self::EDITOR];
    }

    /**
     * @return list<string>
     */
    public static function candidateRoles(): array
    {
        return [self::VIEWER, self::CANDIDATE, self::EDITOR, self::ORG_ADMIN, self::ADMIN];
    }

    public static function canAccessAdminPanel(?string $role): bool
    {
        return in_array((string) $role, self::adminPanelRoles(), true);
    }
}
