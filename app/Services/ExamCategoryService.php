<?php

namespace App\Services;

use App\Models\ExamCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ExamCategoryService
 *
 * Handles business logic for the Exam Category module:
 *   - Hierarchical list for selects
 *   - Tree-builder create / update
 *   - Soft-delete
 */
class ExamCategoryService
{
    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Hierarchical sorted categories with a depth attribute (for select indentation).
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
     * Create an entire category tree from the category-builder payload.
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

            if ($rootNode === null && ! empty($meta['slug'])) {
                $slug = Str::slug($meta['slug']);
            } else {
                $slug = Str::slug($attrs['name'] ?? '');
            }

            $created = ExamCategory::create([
                'organization_id'  => $orgId,
                'parent_id'        => $parentDbId,
                'name'             => trim($attrs['name'] ?? ''),
                'description'      => trim($attrs['description'] ?? ''),
                'status'           => $meta['status'] ?? 'active',
                'meta_title'       => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'meta_keywords'    => $meta['meta_keywords'] ?? null,
                'slug'             => $slug !== '' ? $slug : Str::slug(uniqid('category-', false)),
                'canonical_url'    => $meta['canonical_url'] ?? null,
                'og_title'         => $meta['og_title'] ?? null,
                'og_description'   => $meta['og_description'] ?? null,
                'ai_generated'     => (bool) ($meta['ai_generated'] ?? false),
                'ai_improve'       => (bool) ($meta['ai_improve'] ?? false),
                'created_by'       => Auth::id(),
            ]);

            $idMap[$nodeId] = $created->id;

            if ($rootNode === null) {
                $rootNode = $created;
            }
        }

        return $rootNode;
    }

    /**
     * Update an entire category tree starting from a root category.
     * Missing descendants are soft-deleted.
     */
    public function updateTree(
        ExamCategory $root,
        array        $nodes,
        array        $parentMap,
        array        $meta = [],
    ): ExamCategory {
        $orgId = (int) $root->organization_id;

        $existingIds = [];
        $collectIds = function ($node) use (&$existingIds, &$collectIds) {
            $children = $node->relationLoaded('childrenRecursive')
                ? $node->childrenRecursive
                : $node->children;

            foreach ($children as $child) {
                $existingIds[] = $child->id;
                $collectIds($child);
            }
        };
        $root->loadMissing('childrenRecursive');
        $collectIds($root);

        $allowedIds = array_merge([(int) $root->id], $existingIds);

        $idMap = [];
        $submittedDbIds = [];

        foreach ($nodes as $nodeId => $attrs) {
            $parentNodeId = $parentMap[$nodeId] ?? null;
            $nodeDbId = isset($attrs['id']) ? (int) $attrs['id'] : null;

            $parentDbId = null;
            if ($parentNodeId) {
                $parentDbId = $idMap[$parentNodeId] ?? null;
                if (! $parentDbId && isset($nodes[$parentNodeId]['id'])) {
                    $parentDbId = (int) $nodes[$parentNodeId]['id'];
                }
            } elseif ($nodeDbId && $nodeDbId === (int) $root->id) {
                $parentDbId = $root->parent_id;
            }

            if ($nodeDbId && $nodeDbId === (int) $root->id && ! empty($meta['slug'])) {
                $slug = Str::slug($meta['slug']);
            } else {
                $slug = Str::slug($attrs['name'] ?? '');
            }

            $fields = [
                'parent_id'        => $parentDbId,
                'name'             => trim($attrs['name'] ?? ''),
                'description'      => trim($attrs['description'] ?? ''),
                'status'           => $meta['status'] ?? 'active',
                'meta_title'       => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'meta_keywords'    => $meta['meta_keywords'] ?? null,
                'slug'             => $slug !== '' ? $slug : Str::slug(uniqid('category-', false)),
                'canonical_url'    => $meta['canonical_url'] ?? null,
                'og_title'         => $meta['og_title'] ?? null,
                'og_description'   => $meta['og_description'] ?? null,
                'ai_generated'     => (bool) ($meta['ai_generated'] ?? false),
                'ai_improve'       => (bool) ($meta['ai_improve'] ?? false),
            ];

            if ($nodeDbId) {
                abort_unless(in_array($nodeDbId, $allowedIds, true), 403, 'Invalid category node for this tree.');

                $category = ExamCategory::query()
                    ->forOrg($orgId)
                    ->findOrFail($nodeDbId);

                $category->update($fields);
                $idMap[$nodeId] = $nodeDbId;
                $submittedDbIds[] = $nodeDbId;
            } else {
                $fields['organization_id'] = $orgId;
                $fields['created_by'] = Auth::id();
                $created = ExamCategory::create($fields);
                $idMap[$nodeId] = $created->id;
                $submittedDbIds[] = $created->id;
            }
        }

        $toDeleteIds = array_diff($existingIds, $submittedDbIds);
        if (! empty($toDeleteIds)) {
            foreach (ExamCategory::query()->forOrg($orgId)->whereIn('id', $toDeleteIds)->get() as $toDelete) {
                $toDelete->delete();
            }
        }

        return $root->fresh();
    }

    /**
     * Soft-delete a category (children and exams cascade via model hook).
     */
    public function delete(ExamCategory $category): bool
    {
        return (bool) $category->delete();
    }
}
