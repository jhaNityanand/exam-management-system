<?php

namespace App\Services;

use App\Support\UniqueOrgSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Shared hierarchical category tree operations for Exam / Question / Blog categories.
 */
class HierarchicalCategoryService
{
    /**
     * @param  class-string<Model>  $modelClass
     * @return list<Model>
     */
    public function getHierarchicalList(string $modelClass, int $orgId): array
    {
        $categories = $modelClass::query()
            ->forOrg($orgId)
            ->where('status', 'active')
            ->get(['id', 'name', 'parent_id', 'sort_order']);

        $grouped = $categories->groupBy('parent_id');

        $result = [];
        $traverse = function ($parentId = null, $depth = 0) use ($grouped, &$result, &$traverse) {
            $items = $grouped->get($parentId, collect([]));
            $items = $items->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ]);
            foreach ($items as $item) {
                $item->depth = $depth;
                $result[] = $item;
                $traverse($item->id, $depth + 1);
            }
        };

        $traverse(null, 0);

        return $result;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, array>  $nodes
     * @param  array<string, string|null>  $parentMap
     * @param  array<string, mixed>  $meta
     */
    public function createTree(
        string $modelClass,
        int $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): Model {
        $idMap = [];
        $rootNode = null;
        $sortOrder = 0;
        $reserved = [];

        foreach ($nodes as $nodeId => $attrs) {
            $parentNodeId = $parentMap[$nodeId] ?? null;
            $parentDbId = $parentNodeId ? ($idMap[$parentNodeId] ?? null) : null;

            $slugSource = ($rootNode === null && ! empty($meta['slug']))
                ? $meta['slug']
                : ($attrs['name'] ?? '');

            $created = $modelClass::create([
                'organization_id' => $orgId,
                'parent_id' => $parentDbId,
                'name' => trim($attrs['name'] ?? ''),
                'description' => trim($attrs['description'] ?? ''),
                'status' => $meta['status'] ?? 'active',
                'sort_order' => $sortOrder++,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'meta_keywords' => $meta['meta_keywords'] ?? null,
                'slug' => UniqueOrgSlug::make(
                    $slugSource,
                    fn () => $modelClass::query()->forOrg($orgId),
                    null,
                    $reserved
                ),
                'canonical_url' => $meta['canonical_url'] ?? null,
                'og_title' => $meta['og_title'] ?? null,
                'og_description' => $meta['og_description'] ?? null,
                'ai_generated' => (bool) ($meta['ai_generated'] ?? false),
                'ai_improve' => (bool) ($meta['ai_improve'] ?? false),
                'created_by' => Auth::id(),
            ]);

            $idMap[$nodeId] = $created->id;

            if ($rootNode === null) {
                $rootNode = $created;
            }
        }

        return $rootNode;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, array>  $nodes
     * @param  array<string, string|null>  $parentMap
     * @param  array<string, mixed>  $meta
     */
    public function updateTree(
        string $modelClass,
        Model $root,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): Model {
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
        $sortOrder = 0;
        $reserved = [];

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

            $slugSource = ($nodeDbId && $nodeDbId === (int) $root->id && ! empty($meta['slug']))
                ? $meta['slug']
                : ($attrs['name'] ?? '');

            $fields = [
                'parent_id' => $parentDbId,
                'name' => trim($attrs['name'] ?? ''),
                'description' => trim($attrs['description'] ?? ''),
                'status' => $meta['status'] ?? 'active',
                'sort_order' => $sortOrder++,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'meta_keywords' => $meta['meta_keywords'] ?? null,
                'slug' => UniqueOrgSlug::make(
                    $slugSource,
                    fn () => $modelClass::query()->forOrg($orgId),
                    $nodeDbId,
                    $reserved
                ),
                'canonical_url' => $meta['canonical_url'] ?? null,
                'og_title' => $meta['og_title'] ?? null,
                'og_description' => $meta['og_description'] ?? null,
                'ai_generated' => (bool) ($meta['ai_generated'] ?? false),
                'ai_improve' => (bool) ($meta['ai_improve'] ?? false),
            ];

            if ($nodeDbId) {
                abort_unless(in_array($nodeDbId, $allowedIds, true), 403, 'Invalid category node for this tree.');

                $category = $modelClass::query()
                    ->forOrg($orgId)
                    ->findOrFail($nodeDbId);

                $category->update($fields);
                $idMap[$nodeId] = $nodeDbId;
                $submittedDbIds[] = $nodeDbId;
            } else {
                $fields['organization_id'] = $orgId;
                $fields['created_by'] = Auth::id();
                $created = $modelClass::create($fields);
                $idMap[$nodeId] = $created->id;
                $submittedDbIds[] = $created->id;
            }
        }

        $toDeleteIds = array_diff($existingIds, $submittedDbIds);
        if (! empty($toDeleteIds)) {
            foreach ($modelClass::query()->forOrg($orgId)->whereIn('id', $toDeleteIds)->get() as $toDelete) {
                $toDelete->delete();
            }
        }

        return $root->fresh();
    }

    public function delete(Model $category): bool
    {
        return (bool) $category->delete();
    }
}
