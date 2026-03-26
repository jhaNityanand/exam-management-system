<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DatatableQuery
{
    public static function apply(Builder $query, Request $request, array $searchableColumns, string $defaultSort = 'id'): Builder
    {
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $col) {
                    $q->orWhere($col, 'like', '%'.$search.'%');
                }
            });
        }

        $filters = $request->query('filters', []);
        if (is_array($filters)) {
            foreach ($filters as $column => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }

        $sort = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $request->query('sort', $defaultSort));
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort ?: $defaultSort, $direction);

        return $query;
    }

    public static function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min($max, max(5, (int) $request->query('per_page', $default)));
    }
}
