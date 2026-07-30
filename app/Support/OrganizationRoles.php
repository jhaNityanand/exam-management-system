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

    /**
     * Human-readable label for public author / org roles.
     */
    public static function label(?string $role): string
    {
        return match ((string) $role) {
            self::ADMIN => 'Administrator',
            self::ORG_ADMIN => 'Organization Admin',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
            self::CANDIDATE => 'Candidate',
            default => 'Author',
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
            self::EDITOR => 'Editor',
            default => 'Author',
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
            self::EDITOR => self::label(self::EDITOR),
        ];
    }
}
