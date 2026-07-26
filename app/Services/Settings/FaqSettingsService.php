<?php

namespace App\Services\Settings;

use App\Models\Cms\Faq;
use App\Models\Cms\FaqCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FaqSettingsService
{
    /**
     * @param  array{search?: string, status?: string, category_id?: int|string, per_page?: int}  $filters
     */
    public function paginate(?int $orgId, array $filters = []): LengthAwarePaginator
    {
        $orgId ??= current_organization_id();
        $perPage = max(5, min(50, (int) ($filters['per_page'] ?? 10)));

        $query = Faq::query()
            ->with('category:id,name')
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->ordered()
            ->orderByDesc('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', '%'.$search.'%')
                    ->orWhere('answer', 'like', '%'.$search.'%');
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $categoryId = $filters['category_id'] ?? null;
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('faq_category_id', (int) $categoryId);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, FaqCategory>
     */
    public function categories(?int $orgId): Collection
    {
        $orgId ??= current_organization_id();

        return FaqCategory::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->ordered()
            ->get(['id', 'name', 'status', 'sort_order']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $orgId): Faq
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        return Faq::query()->create([
            'organization_id' => $orgId,
            'faq_category_id' => $data['faq_category_id'] ?: null,
            'question' => $data['question'],
            'answer' => $data['answer'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => $data['status'] ?? 'active',
        ])->load('category:id,name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Faq $faq, array $data, ?int $orgId): Faq
    {
        $this->assertOrg($faq, $orgId);

        $faq->update([
            'faq_category_id' => array_key_exists('faq_category_id', $data) ? ($data['faq_category_id'] ?: null) : $faq->faq_category_id,
            'question' => $data['question'] ?? $faq->question,
            'answer' => $data['answer'] ?? $faq->answer,
            'is_featured' => array_key_exists('is_featured', $data) ? (bool) $data['is_featured'] : $faq->is_featured,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $faq->sort_order,
            'status' => $data['status'] ?? $faq->status,
        ]);

        return $faq->fresh()->load('category:id,name');
    }

    public function delete(Faq $faq, ?int $orgId): void
    {
        $this->assertOrg($faq, $orgId);
        $faq->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data, ?int $orgId): FaqCategory
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        $name = trim((string) $data['name']);
        $slugBase = Str::slug($name) ?: 'faq-category';
        $slug = $slugBase;
        $i = 1;
        while (
            FaqCategory::query()
                ->where('organization_id', $orgId)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $slugBase.'-'.$i;
            $i++;
        }

        return FaqCategory::query()->create([
            'organization_id' => $orgId,
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'faq_category_id' => $faq->faq_category_id,
            'category_name' => $faq->category?->name,
            'is_featured' => (bool) $faq->is_featured,
            'sort_order' => (int) $faq->sort_order,
            'status' => $faq->status,
            'updated_at' => optional($faq->updated_at)?->toDateTimeString(),
        ];
    }

    protected function assertOrg(Faq $faq, ?int $orgId): void
    {
        $orgId ??= current_organization_id();
        abort_unless((int) $faq->organization_id === (int) $orgId, 404);
    }
}
