<?php

namespace App\Services;

use App\Models\BlogCategory;

/**
 * Thin facade over HierarchicalCategoryService for blog categories.
 */
class BlogCategoryService
{
    public function __construct(protected HierarchicalCategoryService $tree) {}

    public function getHierarchicalList(int $orgId): array
    {
        return $this->tree->getHierarchicalList(BlogCategory::class, $orgId);
    }

    public function createTree(
        int $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): BlogCategory {
        /** @var BlogCategory */
        return $this->tree->createTree(BlogCategory::class, $orgId, $nodes, $parentMap, $meta);
    }

    public function updateTree(
        BlogCategory $root,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): BlogCategory {
        /** @var BlogCategory */
        return $this->tree->updateTree(BlogCategory::class, $root, $nodes, $parentMap, $meta);
    }

    public function delete(BlogCategory $category): bool
    {
        return $this->tree->delete($category);
    }
}
