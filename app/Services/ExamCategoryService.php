<?php

namespace App\Services;

use App\Models\ExamCategory;

/**
 * Thin facade over HierarchicalCategoryService for exam categories.
 */
class ExamCategoryService
{
    public function __construct(protected HierarchicalCategoryService $tree) {}

    public function getHierarchicalList(int $orgId): array
    {
        return $this->tree->getHierarchicalList(ExamCategory::class, $orgId);
    }

    public function createTree(
        int $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): ExamCategory {
        /** @var ExamCategory */
        return $this->tree->createTree(ExamCategory::class, $orgId, $nodes, $parentMap, $meta);
    }

    public function updateTree(
        ExamCategory $root,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): ExamCategory {
        /** @var ExamCategory */
        return $this->tree->updateTree(ExamCategory::class, $root, $nodes, $parentMap, $meta);
    }

    public function delete(ExamCategory $category): bool
    {
        return $this->tree->delete($category);
    }
}
