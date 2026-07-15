<?php

namespace App\Services;

use App\Models\NewsCategory;

/**
 * Thin facade over HierarchicalCategoryService for blog categories.
 */
class NewsCategoryService
{
    public function __construct(protected HierarchicalCategoryService $tree) {}

    public function getHierarchicalList(int $orgId): array
    {
        return $this->tree->getHierarchicalList(NewsCategory::class, $orgId);
    }

    public function createTree(
        int $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): NewsCategory {
        /** @var NewsCategory */
        return $this->tree->createTree(NewsCategory::class, $orgId, $nodes, $parentMap, $meta);
    }

    public function updateTree(
        NewsCategory $root,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): NewsCategory {
        /** @var NewsCategory */
        return $this->tree->updateTree(NewsCategory::class, $root, $nodes, $parentMap, $meta);
    }

    public function delete(NewsCategory $category): bool
    {
        return $this->tree->delete($category);
    }
}
