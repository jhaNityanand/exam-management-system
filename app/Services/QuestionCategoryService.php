<?php

namespace App\Services;

use App\Models\QuestionCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * QuestionCategoryService
 *
 * Handles business logic for the Question Category module:
 *   - Hierarchical list for selects
 *   - Tree-builder create / update
 *   - Soft-delete
 */
class QuestionCategoryService
{
    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Hierarchical sorted categories with a depth attribute (for select indentation).
     */
    public function getHierarchicalList(int $orgId): array
    {
        $categories = QuestionCategory::forOrg($orgId)
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

            // Root node may use an explicit SEO slug from the form.
            if ($rootNode === null && ! empty($meta['slug'])) {
                $slug = Str::slug($meta['slug']);
            } else {
                $slug = Str::slug($attrs['name'] ?? '');
            }

            $created = QuestionCategory::create([
                'organization_id' => $orgId,
                'parent_id'       => $parentDbId,
                'name'            => trim($attrs['name'] ?? ''),
                'description'     => trim($attrs['description'] ?? ''),
                'status'          => $meta['status']       ?? 'active',
                'meta_title'      => $meta['meta_title']   ?? null,
                'meta_description'=> $meta['meta_description'] ?? null,
                'meta_keywords'   => $meta['meta_keywords'] ?? null,
                'slug'            => $slug !== '' ? $slug : Str::slug(uniqid('category-', false)),
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
     * Update an entire category tree starting from a root category.
     * Any existing children that are missing from the updated payload will be soft-deleted.
     */
    public function updateTree(
        QuestionCategory $root,
        array            $nodes,
        array            $parentMap,
        array            $meta = [],
    ): QuestionCategory {
        $orgId = (int) $root->organization_id;

        // 1. Gather all existing descendant IDs recursively to detect deletions
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
        // Ensure descendants are available for collection
        $root->loadMissing('childrenRecursive');
        $collectIds($root);

        $allowedIds = array_merge([(int) $root->id], $existingIds);

        // Map nodeId (e.g. "node-0", "node-1") to DB ID
        $idMap = [];
        $submittedDbIds = [];

        // 2. Create or update nodes submitted from the hierarchy canvas
        foreach ($nodes as $nodeId => $attrs) {
            $parentNodeId = $parentMap[$nodeId] ?? null;
            $nodeDbId = isset($attrs['id']) ? (int) $attrs['id'] : null;

            // Resolve parent DB ID
            $parentDbId = null;
            if ($parentNodeId) {
                $parentDbId = $idMap[$parentNodeId] ?? null;
                if (! $parentDbId && isset($nodes[$parentNodeId]['id'])) {
                    $parentDbId = (int) $nodes[$parentNodeId]['id'];
                }
            } elseif ($nodeDbId && $nodeDbId === (int) $root->id) {
                // Form root keeps its existing parent (editing a nested category must not detach it).
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

                $category = QuestionCategory::query()
                    ->forOrg($orgId)
                    ->findOrFail($nodeDbId);

                $category->update($fields);
                $idMap[$nodeId] = $nodeDbId;
                $submittedDbIds[] = $nodeDbId;
            } else {
                $fields['organization_id'] = $orgId;
                $fields['created_by'] = Auth::id();
                $created = QuestionCategory::create($fields);
                $idMap[$nodeId] = $created->id;
                $submittedDbIds[] = $created->id;
            }
        }

        // 3. Identify and delete nodes that were removed from the builder
        $toDeleteIds = array_diff($existingIds, $submittedDbIds);
        if (! empty($toDeleteIds)) {
            foreach (QuestionCategory::query()->forOrg($orgId)->whereIn('id', $toDeleteIds)->get() as $toDelete) {
                $toDelete->delete();
            }
        }

        return $root->fresh();
    }

    /**
     * Soft-delete a category (children and questions cascade via model booted hook).
     */
    public function delete(QuestionCategory $category): bool
    {
        return (bool) $category->delete();
    }
}
