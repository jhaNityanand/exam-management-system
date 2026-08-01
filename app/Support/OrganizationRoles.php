<?php

namespace App\Support;

final class OrganizationRoles
{
    public const ADMIN = 'admin';

    public const ORG_ADMIN = 'org_admin';

    public const CANDIDATE = 'candidate';

    /**
     * Legacy roles retained for existing rows / labels only.
     *
     * @deprecated Prefer ADMIN, ORG_ADMIN, or CANDIDATE.
     */
    public const EDITOR = 'editor';

    /**
     * @deprecated Prefer CANDIDATE.
     */
    public const VIEWER = 'viewer';

    /**
     * Roles that may access the admin panel.
     *
     * @return list<string>
     */
    public static function adminPanelRoles(): array
    {
        return [self::ADMIN, self::ORG_ADMIN];
    }

    /**
     * Roles treated as exam candidates (frontend account area).
     *
     * @return list<string>
     */
    public static function candidateRoles(): array
    {
        return [self::CANDIDATE];
    }

    /**
     * All supported organization roles.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::ADMIN, self::ORG_ADMIN, self::CANDIDATE];
    }

    public static function canAccessAdminPanel(?string $role): bool
    {
        return in_array((string) $role, self::adminPanelRoles(), true);
    }

    /**
     * Human-readable label for public author / org roles.
     */
    public static function label(?string $role): string
    {
        return match ((string) $role) {
            self::ADMIN => 'Administrator',
            self::ORG_ADMIN => 'Organization Admin',
            self::CANDIDATE => 'Candidate',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
            default => 'Member',
        };
    }

    /**
     * Short badge label for author cards.
     */
    public static function shortLabel(?string $role): string
    {
        return match ((string) $role) {
            self::ADMIN => 'Admin',
            self::ORG_ADMIN => 'Org Admin',
            self::CANDIDATE => 'Candidate',
            self::EDITOR => 'Editor',
            default => 'Member',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function publicAuthorLabels(): array
    {
        return [
            self::ADMIN => self::label(self::ADMIN),
            self::ORG_ADMIN => self::label(self::ORG_ADMIN),
        ];
    }
}
