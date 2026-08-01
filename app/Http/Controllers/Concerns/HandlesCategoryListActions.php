<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Models\UserOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    /**
     * Active org members for Created By filters.
     *
     * @return Collection<int, User>
     */
    protected function organizationCreators(int $orgId): Collection
    {
        $userIds = UserOrganization::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Filter a flat category collection and rebuild the visible tree
     * (matched nodes keep their ancestors for hierarchy display).
     *
     * @param  Collection<int, Model>  $allCategories
     * @param  list<int|string>  $createdBy
     * @return Collection<int, Model>
     */
    protected function buildFilteredCategoryTree(
        Collection $allCategories,
        string $search = '',
        string $status = '',
        array $createdBy = [],
        ?string $createdFrom = null,
        ?string $createdTo = null,
    ): Collection {
        $search = trim($search);
        $status = trim($status);
        $createdBy = array_values(array_filter(array_map('strval', $createdBy), fn ($id) => $id !== ''));
        $fromDate = $this->parseCategoryFilterDate($createdFrom);
        $toDate = $this->parseCategoryFilterDate($createdTo);

        $matchedIds = [];
        foreach ($allCategories as $cat) {
            $statusMatches = $status === '' || (string) $cat->status === $status;
            $searchMatches = $search === '' ||
                Str::contains(strtolower((string) $cat->name), strtolower($search)) ||
                Str::contains(strtolower((string) ($cat->description ?? '')), strtolower($search));
            $createdByMatches = $createdBy === [] || in_array((string) ($cat->created_by ?? ''), $createdBy, true);
            $dateMatches = $this->categoryMatchesCreatedRange($cat, $fromDate, $toDate);

            if ($statusMatches && $searchMatches && $createdByMatches && $dateMatches) {
                $matchedIds[$cat->id] = true;
            }
        }

        $catMap = [];
        foreach ($allCategories as $cat) {
            $catMap[$cat->id] = $cat;
        }

        $keptIds = [];
        foreach ($matchedIds as $id => $true) {
            $curr = $catMap[$id] ?? null;
            while ($curr) {
                $keptIds[$curr->id] = true;
                $curr = $curr->parent_id ? ($catMap[$curr->parent_id] ?? null) : null;
            }
        }

        $roots = [];
        $childrenMap = [];
        foreach ($allCategories as $cat) {
            if (! isset($keptIds[$cat->id])) {
                continue;
            }
            $cat->setRelation('children', collect([]));
            if (empty($cat->parent_id)) {
                $roots[] = $cat;
            } else {
                $childrenMap[$cat->parent_id][] = $cat;
            }
        }

        foreach ($allCategories as $cat) {
            if (isset($childrenMap[$cat->id])) {
                $cat->setRelation('children', collect($childrenMap[$cat->id]));
            }
        }

        return collect($roots);
    }

    private function parseCategoryFilterDate(?string $value): ?CarbonImmutable
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));
            $errors = CarbonImmutable::getLastErrors();
            if ($date === false || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))) {
                return null;
            }

            return $date;
        } catch (\Throwable) {
            return null;
        }
    }

    private function categoryMatchesCreatedRange(Model $cat, ?CarbonImmutable $from, ?CarbonImmutable $to): bool
    {
        if (! $from && ! $to) {
            return true;
        }

        $createdAt = $cat->created_at;
        if (! $createdAt) {
            return false;
        }

        $day = CarbonImmutable::parse($createdAt->toDateString(), config('app.timezone'))->startOfDay();
        if ($from && $day->lt($from->startOfDay())) {
            return false;
        }
        if ($to && $day->gt($to->startOfDay())) {
            return false;
        }

        return true;
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
