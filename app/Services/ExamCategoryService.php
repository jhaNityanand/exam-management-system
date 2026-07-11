<?php

namespace App\Services;

use App\Models\ExamCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ExamCategoryService
 *
 * Handles all business logic for the Exam Category module.
 */
class ExamCategoryService
{
    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Return paginated root categories.
     */
    public function paginateRootsForOrg(
        int    $orgId,
        int    $perPage = 15,
        string $search  = '',
        string $status  = '',
        string $sort    = 'name_asc',
    ): LengthAwarePaginator {
        $query = ExamCategory::forOrg($orgId)
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
     * Return full tree for org.
     */
    public function treeForOrg(int $orgId): Collection
    {
        return ExamCategory::forOrg($orgId)
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
     * Flat list of all categories for select dropdowns.
     */
    public function listForSelect(int $orgId, int|null $excludeId = null): Collection
    {
        $query = ExamCategory::forOrg($orgId)->orderBy('name');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get(['id', 'name', 'parent_id']);
    }

    /**
     * Get a hierarchical sorted array of categories with a depth attribute.
     */
    public function getHierarchicalList(int $orgId): array
    {
        $categories = ExamCategory::forOrg($orgId)
            ->where('status', 'active')
            ->get(['id', 'name', 'parent_id']);

        $grouped = $categories->groupBy('parent_id');

        $result = [];
        $traverse = function ($parentId = null, $depth = 0) use ($grouped, &$result, &$traverse) {
            $items = $grouped->get($parentId, collect([]));
            $items = $items->sortBy('name');
            foreach ($items as $item) {
                $item->depth = $depth;
                $result[] = $item;
                $traverse($item->id, $depth + 1);
            }
        };

        $traverse(null, 0);

        return $result;
    }

    // ── Write ────────────────────────────────────────────────────────────────

    /**
     * Create a single category.
     */
    public function create(array $data): ExamCategory
    {
        $data['created_by']    = Auth::id();
        $data['organization_id'] = $data['organization_id'] ?? null;

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return ExamCategory::create($data);
    }

    /**
     * Create an entire category tree.
     */
    public function createTree(
        int   $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): ExamCategory {
        $idMap    = [];
        $rootNode = null;

        foreach ($nodes as $nodeId => $attrs) {
            $parentNodeId = $parentMap[$nodeId] ?? null;
            $parentDbId   = $parentNodeId ? ($idMap[$parentNodeId] ?? null) : null;

            $slug = Str::slug($attrs['name'] ?? '');

            $created = ExamCategory::create([
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
     */
    public function update(ExamCategory $category, array $data): ExamCategory
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Update an entire category tree.
     */
    public function updateTree(
        ExamCategory $root,
        array        $nodes,
        array        $parentMap,
        array        $meta = [],
    ): ExamCategory {
        $orgId = $root->organization_id;

        // 1. Gather all existing descendant IDs
        $existingIds = [];
        $collectIds = function ($node) use (&$existingIds, &$collectIds) {
            foreach ($node->children as $child) {
                $existingIds[] = $child->id;
                $collectIds($child);
            }
        };
        $collectIds($root);

        // 2. Perform upsert mapping
        $idMap = [];
        $savedIds = [];

        foreach ($nodes as $nodeId => $attrs) {
            $parentNodeId = $parentMap[$nodeId] ?? null;
            $parentDbId = null;

            if ($parentNodeId === 'root') {
                $parentDbId = $root->id;
            } elseif ($parentNodeId) {
                $parentDbId = $idMap[$parentNodeId] ?? null;
            }

            $slug = Str::slug($attrs['name'] ?? '');

            $dbId = $attrs['id'] ?? null;

            if ($dbId && in_array((int)$dbId, $existingIds)) {
                // Update child
                $child = ExamCategory::findOrFail($dbId);
                $child->update([
                    'parent_id'        => $parentDbId,
                    'name'             => trim($attrs['name'] ?? ''),
                    'description'      => trim($attrs['description'] ?? ''),
                    'status'           => $meta['status']       ?? 'active',
                    'meta_title'       => $meta['meta_title']   ?? null,
                    'meta_description' => $meta['meta_description'] ?? null,
                    'meta_keywords'    => $meta['meta_keywords'] ?? null,
                    'slug'             => $slug,
                    'canonical_url'    => $meta['canonical_url'] ?? null,
                    'og_title'         => $meta['og_title']      ?? null,
                    'og_description'   => $meta['og_description'] ?? null,
                    'ai_generated'     => (bool) ($meta['ai_generated'] ?? false),
                    'ai_improve'       => (bool) ($meta['ai_improve']   ?? false),
                    'updated_by'       => Auth::id(),
                ]);
                $idMap[$nodeId] = $child->id;
                $savedIds[] = $child->id;
            } elseif ($dbId == $root->id || $nodeId === 'root') {
                // Update root
                $root->update([
                    'name'             => trim($attrs['name'] ?? $root->name),
                    'description'      => trim($attrs['description'] ?? $root->description),
                    'status'           => $meta['status']       ?? $root->status,
                    'meta_title'       => $meta['meta_title']   ?? $root->meta_title,
                    'meta_description' => $meta['meta_description'] ?? $root->meta_description,
                    'meta_keywords'    => $meta['meta_keywords'] ?? $root->meta_keywords,
                    'slug'             => empty($meta['slug']) ? Str::slug($attrs['name'] ?? $root->name) : $meta['slug'],
                    'canonical_url'    => $meta['canonical_url'] ?? $root->canonical_url,
                    'og_title'         => $meta['og_title']      ?? $root->og_title,
                    'og_description'   => $meta['og_description'] ?? $root->og_description,
                    'ai_generated'     => isset($meta['ai_generated']) ? (bool)$meta['ai_generated'] : $root->ai_generated,
                    'ai_improve'       => isset($meta['ai_improve']) ? (bool)$meta['ai_improve'] : $root->ai_improve,
                    'updated_by'       => Auth::id(),
                ]);
                $idMap[$nodeId] = $root->id;
            } else {
                // Create new child node
                $newChild = ExamCategory::create([
                    'organization_id'  => $orgId,
                    'parent_id'        => $parentDbId ?: $root->id,
                    'name'             => trim($attrs['name'] ?? ''),
                    'description'      => trim($attrs['description'] ?? ''),
                    'status'           => $meta['status']       ?? 'active',
                    'meta_title'       => $meta['meta_title']   ?? null,
                    'meta_description' => $meta['meta_description'] ?? null,
                    'meta_keywords'    => $meta['meta_keywords'] ?? null,
                    'slug'             => $slug,
                    'canonical_url'    => $meta['canonical_url'] ?? null,
                    'og_title'         => $meta['og_title']      ?? null,
                    'og_description'   => $meta['og_description'] ?? null,
                    'ai_generated'     => (bool) ($meta['ai_generated'] ?? false),
                    'ai_improve'       => (bool) ($meta['ai_improve']   ?? false),
                    'created_by'       => Auth::id(),
                ]);
                $idMap[$nodeId] = $newChild->id;
                $savedIds[] = $newChild->id;
            }
        }

        // 3. Delete missing descendants
        $missingIds = array_diff($existingIds, $savedIds);
        if (! empty($missingIds)) {
            ExamCategory::whereIn('id', $missingIds)->delete();
        }

        return $root->fresh();
    }

    /**
     * Delete category (soft-delete).
     */
    public function delete(ExamCategory $category): void
    {
        $category->delete();
    }
}
