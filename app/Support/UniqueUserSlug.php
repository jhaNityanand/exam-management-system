<?php

namespace App\Support;

use App\Models\User;

/**
 * Builds globally unique user profile slugs.
 */
class UniqueUserSlug
{
    public static function make(string $nameOrSlug, ?int $ignoreId = null): string
    {
        $base = UniqueOrgSlug::normalize($nameOrSlug);
        if ($base === '') {
            $base = 'author';
        }

        $candidate = $base;
        $i = 1;

        while (
            User::query()
                ->withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $suffix = '-'.$i;
            $trimmed = rtrim(substr($base, 0, max(1, UniqueOrgSlug::MAX_LENGTH - strlen($suffix))), '-');
            $candidate = $trimmed.$suffix;
            $i++;
        }

        return $candidate;
    }

    public static function ensureFor(User $user): string
    {
        if (filled($user->slug)) {
            return (string) $user->slug;
        }

        $slug = static::make($user->username ?: $user->name, $user->id);
        $user->forceFill(['slug' => $slug])->save();

        return $slug;
    }
}
