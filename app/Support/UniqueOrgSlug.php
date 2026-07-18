<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Builds organization-scoped unique slugs for tables with
 * unique(organization_id, slug) constraints.
 *
 * Collision suffixes follow: base, base-1, base-2, …
 */
class UniqueOrgSlug
{
    public const MAX_LENGTH = 80;

    /**
     * @param  callable(Builder): Builder  $queryFactory  Receives a fresh query for existence checks
     * @param  list<string>                $reserved      Slugs already claimed in the current batch
     */
    public static function make(
        string $titleOrSlug,
        callable $queryFactory,
        ?int $ignoreId = null,
        array &$reserved = [],
    ): string {
        $base = static::normalize($titleOrSlug);
        if ($base === '') {
            $base = static::normalize(uniqid('item-', false));
        }

        $candidate = $base;
        $i = 1;

        while (
            in_array($candidate, $reserved, true)
            || static::exists($queryFactory, $candidate, $ignoreId)
        ) {
            $suffix = '-'.$i;
            $trimmedBase = static::fitBase($base, strlen($suffix));
            $candidate = $trimmedBase.$suffix;
            $i++;
        }

        $reserved[] = $candidate;

        return $candidate;
    }

    /**
     * Convenience helper for Eloquent models that use SoftDeletes + forOrg().
     *
     * @param  class-string<Model>  $modelClass
     * @param  list<string>         $reserved
     */
    public static function forModel(
        string $modelClass,
        string $titleOrSlug,
        int $orgId,
        ?int $ignoreId = null,
        array &$reserved = [],
    ): string {
        return static::make(
            $titleOrSlug,
            fn () => $modelClass::query()->withTrashed()->forOrg($orgId),
            $ignoreId,
            $reserved
        );
    }

    /**
     * Normalize a title/name into a short SEO-friendly slug (no uniqueness).
     */
    public static function normalize(string $titleOrSlug): string
    {
        $base = Str::slug(trim(strip_tags($titleOrSlug)));

        return static::fitBase($base, 0);
    }

    protected static function fitBase(string $base, int $suffixLength): string
    {
        $max = max(1, static::MAX_LENGTH - $suffixLength);
        if (strlen($base) <= $max) {
            return $base;
        }

        return rtrim(substr($base, 0, $max), '-');
    }

    protected static function exists(callable $queryFactory, string $slug, ?int $ignoreId): bool
    {
        /** @var Builder $query */
        $query = $queryFactory();

        return $query
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}
