<?php

namespace App\Services;

use App\Models\QuestionCategory;

/**
 * Thin facade over HierarchicalCategoryService for question categories.
 */
class QuestionCategoryService
{
    public function __construct(protected HierarchicalCategoryService $tree) {}

    public function getHierarchicalList(int $orgId): array
    {
        return $this->tree->getHierarchicalList(QuestionCategory::class, $orgId);
    }

    public function createTree(
        int $orgId,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): QuestionCategory {
        /** @var QuestionCategory */
        return $this->tree->createTree(QuestionCategory::class, $orgId, $nodes, $parentMap, $meta);
    }

    public function updateTree(
        QuestionCategory $root,
        array $nodes,
        array $parentMap,
        array $meta = [],
    ): QuestionCategory {
        /** @var QuestionCategory */
        return $this->tree->updateTree(QuestionCategory::class, $root, $nodes, $parentMap, $meta);
    }

    public function delete(QuestionCategory $category): bool
    {
        return $this->tree->delete($category);
    }
}
