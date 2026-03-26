<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CategoryService
{
    public function paginateForOrg(int $orgId, int $perPage = 15): LengthAwarePaginator
    {
        return Category::forOrg($orgId)
            ->with('parent')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function treeForOrg(int $orgId, bool $rootsOnly = false): Collection
    {
        $q = Category::forOrg($orgId)->orderBy('name');
        if ($rootsOnly) {
            $q->roots();
        }

        return $q->with([
            'children' => fn ($c) => $c->orderBy('name')->with([
                'children' => fn ($c2) => $c2->orderBy('name'),
            ]),
        ])->get();
    }

    public function create(array $data): Category
    {
        $data['created_by'] = Auth::id();

        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function listForSelect(int $orgId): Collection
    {
        return Category::forOrg($orgId)->orderBy('name')->get(['id', 'name', 'parent_id']);
    }
}
