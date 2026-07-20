<?php

namespace Database\Seeders;

use App\Models\QuestionCategory;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds interview / competitive-exam oriented question categories for demo-org.
 */
class QuestionCategorySeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        $editor = $this->demoEditor();

        if (! $org || ! $editor) {
            $this->command?->warn('QuestionCategorySeeder: demo-org or editor missing. Skipping.');

            return;
        }

        // Replace the previous demo taxonomy so re-seeding stays focused and useful.
        QuestionCategory::query()
            ->withTrashed()
            ->where('organization_id', $org->id)
            ->forceDelete();

        $sort = 1;
        foreach ($this->categories() as $item) {
            QuestionCategory::query()->create([
                'organization_id' => $org->id,
                'parent_id' => null,
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'status' => 'active',
                'is_public' => true,
                'sort_order' => $sort++,
                'created_by' => $editor->id,
                'meta_title' => Str::limit($item['name'].' Question Bank', 255, ''),
                'meta_description' => Str::limit($item['description'], 500, ''),
                'meta_keywords' => implode(', ', [
                    Str::lower($item['name']),
                    'question bank',
                    'practice questions',
                    'assessment',
                ]),
                'canonical_url' => rtrim((string) config('app.url'), '/').'/question-categories/'.$item['slug'],
                'og_title' => $item['name'].' Questions',
                'og_description' => Str::limit($item['description'], 500, ''),
                'robots' => 'index,follow',
                'schema_markup' => json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $item['name'].' Question Bank',
                    'description' => $item['description'],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'ai_generated' => false,
                'ai_improve' => false,
            ]);
        }

        $count = QuestionCategory::query()->forOrg($org->id)->count();
        $this->command?->info("QuestionCategorySeeder: {$count} categories seeded.");
    }

    /**
     * @return list<array{name: string, slug: string, description: string}>
     */
    private function categories(): array
    {
        return [
            [
                'name' => 'BCA',
                'slug' => 'bca',
                'description' => 'Core computer applications topics for BCA students and entrance practice.',
            ],
            [
                'name' => 'MCA',
                'slug' => 'mca',
                'description' => 'Advanced computing concepts for MCA coursework and interview rounds.',
            ],
            [
                'name' => 'PHP',
                'slug' => 'php',
                'description' => 'PHP language fundamentals, OOP, and modern PHP practices.',
            ],
            [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'Laravel framework routing, Eloquent, validation, queues, and security.',
            ],
            [
                'name' => 'JavaScript',
                'slug' => 'javascript',
                'description' => 'JavaScript language features, DOM, async patterns, and ESNext essentials.',
            ],
            [
                'name' => 'Database',
                'slug' => 'database',
                'description' => 'SQL, normalization, indexing, transactions, and relational design.',
            ],
            [
                'name' => 'HTML/CSS',
                'slug' => 'html-css',
                'description' => 'Semantic HTML, accessibility, CSS layout, and responsive design.',
            ],
            [
                'name' => 'Aptitude',
                'slug' => 'aptitude',
                'description' => 'Quantitative aptitude and logical reasoning for placements and exams.',
            ],
            [
                'name' => 'General Knowledge',
                'slug' => 'general-knowledge',
                'description' => 'Current affairs style GK covering technology, India, and fundamentals.',
            ],
            [
                'name' => 'Programming Fundamentals',
                'slug' => 'programming-fundamentals',
                'description' => 'Variables, control flow, OOP basics, complexity, and clean coding.',
            ],
            [
                'name' => 'Data Structures',
                'slug' => 'data-structures',
                'description' => 'Arrays, linked lists, stacks, queues, trees, graphs, and hashing.',
            ],
            [
                'name' => 'Interview Preparation',
                'slug' => 'interview-preparation',
                'description' => 'Behavioral, HR, and technical interview readiness questions.',
            ],
        ];
    }
}
