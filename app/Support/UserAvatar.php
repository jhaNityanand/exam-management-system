<?php

namespace App\Support;

use App\Models\User;
use App\Services\ProfileAvatarService;
use Illuminate\Support\Str;

/**
 * Shared avatar helpers: profile image URL, initials, and consistent color.
 */
class UserAvatar
{
    /**
     * Initials from a display name.
     * "Nityanand Jha" → NJ; "Nityanand" → NI
     */
    public static function initials(?string $name, string $fallback = 'U'): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name) ?? '');
        if ($name === '') {
            return strtoupper(mb_substr($fallback, 0, 2));
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) >= 2) {
            $first = mb_substr($parts[0], 0, 1);
            $last = mb_substr($parts[count($parts) - 1], 0, 1);

            return strtoupper($first.$last);
        }

        $single = $parts[0] ?? $fallback;
        $chars = mb_substr($single, 0, 2);

        return strtoupper($chars !== '' ? $chars : mb_substr($fallback, 0, 2));
    }

    /**
     * Deterministic pastel-ish background from a seed (name/id).
     */
    public static function color(string $seed): string
    {
        $palette = [
            '#0f766e', '#0369a1', '#7c3aed', '#be185d', '#c2410c',
            '#15803d', '#1d4ed8', '#a16207', '#0e7490', '#4f46e5',
        ];

        $hash = 0;
        $len = strlen($seed);
        for ($i = 0; $i < $len; $i++) {
            $hash = (($hash << 5) - $hash) + ord($seed[$i]);
            $hash |= 0;
        }

        return $palette[abs($hash) % count($palette)];
    }

    public static function url(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('profile');
        $path = $user->profile?->avatar;
        if (! $path) {
            return null;
        }

        return app(ProfileAvatarService::class)->url($path);
    }

    /**
     * @return array{url:?string, initials:string, color:string, name:string}
     */
    public static function resolve(?User $user, ?string $nameFallback = null): array
    {
        $name = trim((string) ($user?->name ?: $nameFallback ?: 'User'));
        $seed = (string) ($user?->id ?: Str::slug($name) ?: 'user');

        return [
            'url' => self::url($user),
            'initials' => self::initials($name),
            'color' => self::color($seed),
            'name' => $name,
        ];
    }
}
