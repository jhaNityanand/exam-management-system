<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait HandlesCategoryListActions
{
    abstract protected function categoryModelClass(): string;

    abstract protected function categoryIndexRoute(): string;

    public function restoreCategory(int $id): RedirectResponse
    {
        /** @var Model $model */
        $model = $this->categoryModelClass()::withTrashed()
            ->forOrg($this->currentOrgId())
            ->findOrFail($id);
        abort_unless(method_exists($model, 'trashed') && $model->trashed(), 404);
        $this->restoreCategoryBranch($model);

        return redirect()->route($this->categoryIndexRoute(), ['tab' => 'bin'])
            ->with('success', 'Category restored successfully.');
    }

    public function bulkDestroyCategories(Request $request): RedirectResponse
    {
        $ids = $this->categoryIds($request);
        $count = $this->categoryModelClass()::forOrg($this->currentOrgId())
            ->whereIn('id', $ids)->get()->each->delete()->count();

        return redirect()->route($this->categoryIndexRoute())
            ->with('success', "{$count} category item(s) moved to bin.");
    }

    public function bulkRestoreCategories(Request $request): RedirectResponse
    {
        $ids = $this->categoryIds($request);
        $categories = $this->categoryModelClass()::onlyTrashed()
            ->forOrg($this->currentOrgId())
            ->whereIn('id', $ids)
            ->get();
        $categories->each(fn (Model $category) => $this->restoreCategoryBranch($category));
        $count = $categories->count();

        return redirect()->route($this->categoryIndexRoute(), ['tab' => 'bin'])
            ->with('success', "{$count} category item(s) restored.");
    }

    public function bulkUpdateCategoryStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
        $count = $this->categoryModelClass()::forOrg($this->currentOrgId())
            ->whereIn('id', array_unique($validated['ids']))
            ->update(['status' => $validated['status']]);

        return redirect()->route($this->categoryIndexRoute())
            ->with('success', "Status updated for {$count} category item(s).");
    }

    /** @return list<int> */
    private function categoryIds(Request $request): array
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $validated['ids'])));
    }

    private function restoreCategoryBranch(Model $category): void
    {
        if (method_exists($category, 'trashed') && $category->trashed()) {
            $category->restore();
        }

        foreach (['questions', 'exams', 'blogs', 'news'] as $relation) {
            if (method_exists($category, $relation)) {
                $category->{$relation}()->onlyTrashed()->restore();
            }
        }

        if (method_exists($category, 'children')) {
            $category->children()->withTrashed()->get()
                ->each(fn (Model $child) => $this->restoreCategoryBranch($child));
        }
    }
}
