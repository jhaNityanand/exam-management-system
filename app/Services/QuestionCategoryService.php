<?php

namespace App\Services;

use App\Models\QuestionCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * QuestionCategoryService
 *
 * Handles all business logic for the Question Category module:
 *   - Tree / paginated reads
 *   - Single and bulk (tree-builder) create
 *   - Update and soft-delete
 *   - Flat list for <select> dropdowns
 */
class QuestionCategoryService
{
    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Return paginated root categories (with one level of children eager-loaded).
     * Used by the index page.
     */
    public function paginateRootsForOrg(
        int    $orgId,
        int    $perPage = 15,
        string $search  = '',
        string $status  = '',
        string $sort    = 'name_asc',
    ): LengthAwarePaginator {
        $query = QuestionCategory::forOrg($orgId)
            ->roots()
            ->with([
                'children' => fn ($q) => $q->orderBy('name')
                    ->with([
                        'children' => fn ($q2) => $q2->orderBy('name'),
                    ]),
            ]);

        // Search across name and description
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status !== '') {
            $query->where('status', $status);
        }

        // Sorting
        [$col, $dir] = match ($sort) {
            'name_desc' => ['name', 'desc'],
            'newest'    => ['created_at', 'desc'],
            'oldest'    => ['created_at', 'asc'],
            default     => ['name', 'asc'],
        };

        return $query->orderBy($col, $dir)->paginate($perPage)->withQueryString();
    }

    /**
     * Return full tree for org (all roots → children → grandchildren).
     * Used by the index tree renderer.
     */
    public function treeForOrg(int $orgId): Collection
    {
        return QuestionCategory::forOrg($orgId)
            ->roots()
            ->orderBy('name')
            ->with([
                'children' => fn ($q) => $q->orderBy('name')
                    ->with([
                        'children' => fn ($q2) => $q2->orderBy('name')
                            ->with([
                                'children' => fn ($q3) => $q3->orderBy('name'),
                            ]),
                    ]),
            ])
            ->get();
    }

    /**
     * Flat list of all categories for a <select> dropdown.
     * Parent names are indented with dashes for clarity.
     *
     * @return Collection<int, QuestionCategory>
     */
    public function listForSelect(int $orgId, int|null $excludeId = null): Collection
    {
        $query = QuestionCategory::forOrg($orgId)->orderBy('name');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get(['id', 'name', 'parent_id']);
    }

    // ── Write ────────────────────────────────────────────────────────────────

    /**
     * Create a single category.
     *
     * @param  array<string, mixed>  $data  Validated attributes.
     */
    public function create(array $data): QuestionCategory
    {
        $data['created_by']    = Auth::id();
        $data['organization_id'] = $data['organization_id'] ?? null;

        // Auto-generate slug from name if not provided
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return QuestionCategory::create($data);
    }

    /**
     * Create an entire category tree from the category-builder payload.
     *
     * The form submits:
     *   categories[node-0][name], categories[node-0][description]
     *   categories[node-1][name], categories[node-1][description]
     *   ...
     * plus a hidden _parent_map JSON:
     *   {"node-1": "node-0", "node-2": "node-0", ...}
     *
     * @param  int                    $orgId
     * @param  array<string, array>   $nodes      Flat map of nodeId → {name, description}
     * @param  array<string, string>  $parentMap  Map of nodeId → parentNodeId (or '' for root)
     * @param  array<string, mixed>   $meta       Shared metadata / status / AI flags for all nodes
     * @return QuestionCategory  The root category created.
     */
    public function createTree(
        int   $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): QuestionCategory {
        // Map nodeId → created DB id
        $idMap    = [];
        $rootNode = null;

        // Process nodes in key order (they are ordered by DOM insertion)
        foreach ($nodes as $nodeId => $attrs) {
            $parentNodeId = $parentMap[$nodeId] ?? null;
            $parentDbId   = $parentNodeId ? ($idMap[$parentNodeId] ?? null) : null;

            $slug = Str::slug($attrs['name'] ?? '');

            $created = QuestionCategory::create([
                'organization_id' => $orgId,
                'parent_id'       => $parentDbId,
                'name'            => trim($attrs['name'] ?? ''),
                'description'     => trim($attrs['description'] ?? ''),
                'status'          => $meta['status']       ?? 'active',
                'meta_title'      => $meta['meta_title']   ?? null,
                'meta_description'=> $meta['meta_description'] ?? null,
                'meta_keywords'   => $meta['meta_keywords'] ?? null,
                'slug'            => $slug,
                'canonical_url'   => $meta['canonical_url'] ?? null,
                'og_title'        => $meta['og_title']      ?? null,
                'og_description'  => $meta['og_description'] ?? null,
                'ai_generated'    => (bool) ($meta['ai_generated'] ?? false),
                'ai_improve'      => (bool) ($meta['ai_improve']   ?? false),
                'created_by'      => Auth::id(),
            ]);

            $idMap[$nodeId] = $created->id;

            if ($rootNode === null) {
                $rootNode = $created;
            }
        }

        return $rootNode;
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data  Validated attributes.
     */
    public function update(QuestionCategory $category, array $data): QuestionCategory
    {
        // Auto-generate slug from name if name changed and slug not explicitly set
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Soft-delete a category (children and questions cascade via model booted hook).
     */
    public function delete(QuestionCategory $category): bool
    {
        return (bool) $category->delete();
    }
}
