<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Builds organization-scoped unique slugs for tables with
 * unique(organization_id, slug) constraints.
 */
class UniqueOrgSlug
{
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
        $base = Str::slug($titleOrSlug);
        if ($base === '') {
            $base = Str::slug(uniqid('item-', false));
        }

        $candidate = $base;
        $i = 2;

        while (
            in_array($candidate, $reserved, true)
            || static::exists($queryFactory, $candidate, $ignoreId)
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        $reserved[] = $candidate;

        return $candidate;
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
