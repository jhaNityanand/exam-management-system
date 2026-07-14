<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a tech-education blog category hierarchy for demo-org.
 */
class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $org    = Organization::where('slug', 'demo-org')->firstOrFail();
        $editor = User::where('email', 'editor@examms.test')->firstOrFail();

        $this->insertTree($this->tree(), $org->id, $editor->id, null, '', 0);

        $count = BlogCategory::forOrg($org->id)->count();
        $this->command->info("BlogCategorySeeder: seeded {$count} blog categories.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tree(): array
    {
        return [
            $this->cat('Laravel', 'Guides on routing, Eloquent, Blade, queues, and Laravel ecosystem best practices.', [
                $this->cat('Eloquent', 'ORM patterns, relationships, scopes, and query optimization in Laravel.'),
                $this->cat('Blade', 'Templating, components, layouts, and view composition techniques.'),
                $this->cat('Queues', 'Background jobs, workers, Horizon, and reliable async processing.'),
            ], 'laravel'),
            $this->cat('PHP', 'Core PHP language features, OOP, and modern runtime improvements.', [
                $this->cat('OOP', 'Classes, inheritance, interfaces, traits, and design principles in PHP.'),
                $this->cat('Modern PHP', 'PHP 8+ features, attributes, enums, and type-safe application design.'),
            ], 'php'),
            $this->cat('JavaScript', 'Frontend and backend JavaScript for modern web applications.', [
                $this->cat('Node.js', 'Server-side JavaScript, event loop, APIs, and microservice patterns.'),
                $this->cat('React', 'Component architecture, hooks, server components, and state management.'),
                $this->cat('Vue.js', 'Composition API, reactivity, and scalable Vue 3 application structure.'),
            ], 'javascript'),
            $this->cat('Databases', 'Relational and in-memory data stores for production workloads.', [
                $this->cat('MySQL', 'Schema design, indexing, replication, and query tuning.'),
                $this->cat('PostgreSQL', 'Advanced SQL, JSONB, extensions, and performance planning.'),
                $this->cat('Redis', 'Caching, pub/sub, data structures, and session storage patterns.'),
            ], 'databases'),
            $this->cat('DevOps', 'Containers, CI/CD pipelines, infrastructure automation, and deployment workflows.'),
            $this->cat('Cybersecurity', 'Application security, OWASP risks, hardening, and secure development practices.'),
            $this->cat('Artificial Intelligence', 'Machine learning fundamentals, LLMs, and practical AI for engineering teams.'),
            $this->cat('APIs & Integrations', 'REST, GraphQL, webhooks, authentication, and third-party service design.'),
            $this->cat('Career Guidance', 'Career paths, interview preparation, and professional growth for developers.'),
            $this->cat('Software Engineering', 'Architecture, testing, system design, and maintainable code practices.'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function cat(string $name, string $description, array $children = [], ?string $slug = null, string $status = 'active'): array
    {
        return [
            'name'             => $name,
            'description'      => $description,
            'status'           => $status,
            'slug'             => $slug,
            'meta_title'       => "{$name} — Blog",
            'meta_description' => Str::limit("Articles about {$name}. {$description}", 160),
            'meta_keywords'    => strtolower(str_replace([' and ', ' & ', '/'], [', ', ', ', ', '], $name)),
            'og_title'         => "{$name} Articles",
            'og_description'   => Str::limit($description, 160),
            'children'         => $children,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function insertTree(array $nodes, int $orgId, int $editorId, ?int $parentId, string $slugPrefix, int $sortOffset): void
    {
        foreach ($nodes as $index => $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $explicitSlug = $node['slug'] ?? null;
            $baseSlug     = Str::slug($node['name']);
            $slug         = $explicitSlug ?: ($slugPrefix !== '' ? "{$slugPrefix}-{$baseSlug}" : $baseSlug);
            $node['slug'] = $slug;

            $category = BlogCategory::firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'name'            => $node['name'],
                    'parent_id'       => $parentId,
                ],
                array_merge($node, [
                    'organization_id' => $orgId,
                    'parent_id'       => $parentId,
                    'sort_order'      => $sortOffset + $index + 1,
                    'created_by'      => $editorId,
                    'ai_generated'    => false,
                    'ai_improve'      => false,
                ])
            );

            if (! empty($children)) {
                $this->insertTree($children, $orgId, $editorId, $category->id, $slug, 0);
            }
        }
    }
}
