<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Exam;
use App\Models\News;
use App\Models\Question;
use App\Support\UniqueOrgSlug;
use InvalidArgumentException;

/**
 * Resolves unique organization-scoped slugs for frontend preview and persistence.
 */
class SlugService
{
    /**
     * @var array<string, class-string>
     */
    protected const MODULES = [
        'exam' => Exam::class,
        'question' => Question::class,
        'blog' => Blog::class,
        'news' => News::class,
    ];

    /**
     * @return list<string>
     */
    public function modules(): array
    {
        return array_keys(static::MODULES);
    }

    public function resolve(string $module, string $source, int $orgId, ?int $ignoreId = null): string
    {
        $modelClass = $this->modelClass($module);

        return UniqueOrgSlug::forModel($modelClass, $source, $orgId, $ignoreId);
    }

    /**
     * @return class-string
     */
    public function modelClass(string $module): string
    {
        if (! isset(static::MODULES[$module])) {
            throw new InvalidArgumentException("Unsupported slug module [{$module}].");
        }

        return static::MODULES[$module];
    }
}
