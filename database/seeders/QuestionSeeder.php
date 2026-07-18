<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionCategory;
use App\Support\UniqueOrgSlug;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds curated interview/exam questions from database/seeders/data/questions.
 */
class QuestionSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        $editor = $this->demoEditor();

        if (! $org || ! $editor) {
            $this->command?->warn('QuestionSeeder: demo-org or editor missing. Skipping.');

            return;
        }

        Question::query()
            ->withTrashed()
            ->where('organization_id', $org->id)
            ->forceDelete();

        $categories = QuestionCategory::query()
            ->forOrg($org->id)
            ->get()
            ->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command?->warn('QuestionSeeder: no categories found. Run QuestionCategorySeeder first.');

            return;
        }

        $created = 0;
        $singleMcq = 0;
        $multiMcq = 0;
        $reservedSlugs = [];

        foreach ($categories as $slug => $category) {
            $path = database_path('seeders/data/questions/'.$slug.'.php');
            if (! is_file($path)) {
                $this->command?->warn("QuestionSeeder: missing bank [{$slug}.php].");

                continue;
            }

            /** @var list<array<string, mixed>> $bank */
            $bank = require $path;

            foreach ($bank as $payload) {
                $normalized = $this->normalizePayload($payload);
                $bodyText = strip_tags((string) $normalized['body']);

                Question::query()->create(array_merge($normalized, [
                    'organization_id' => $org->id,
                    'category_id' => $category->id,
                    'created_by' => $editor->id,
                    'status' => 'active',
                    'meta_title' => Str::limit($bodyText, 60, ''),
                    'meta_description' => Str::limit($bodyText, 160, ''),
                    'meta_keywords' => implode(', ', array_filter([
                        $category->name,
                        $normalized['type'] ?? null,
                        $normalized['difficulty'] ?? null,
                        'practice question',
                    ])),
                    'slug' => UniqueOrgSlug::forModel(
                        Question::class,
                        Str::limit($bodyText, 80, ''),
                        (int) $org->id,
                        null,
                        $reservedSlugs,
                    ),
                    'og_title' => Str::limit($bodyText, 70, ''),
                    'og_description' => Str::limit($bodyText, 160, ''),
                    'robots' => 'index,follow',
                    'schema_markup' => json_encode([
                        '@context' => 'https://schema.org',
                        '@type' => 'Question',
                        'name' => Str::limit($bodyText, 110, ''),
                        'eduQuestionType' => $normalized['type'] ?? 'mcq',
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'ai_generated' => false,
                    'ai_improve' => false,
                ]));

                $created++;
                if (($normalized['type'] ?? '') === 'mcq') {
                    if (! empty($normalized['allows_multiple'])) {
                        $multiMcq++;
                    } else {
                        $singleMcq++;
                    }
                }
            }
        }

        $this->command?->info("QuestionSeeder: {$created} questions seeded ({$singleMcq} single MCQ, {$multiMcq} multi MCQ).");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $allowsMultiple = (bool) ($payload['allows_multiple'] ?? false);

        if ($allowsMultiple) {
            $answers = array_values(array_filter($payload['correct_answers'] ?? []));
            $payload['correct_answers'] = $answers;
            $payload['correct_answer'] = (string) ($answers[0] ?? ($payload['correct_answer'] ?? ''));
        } else {
            $payload['correct_answers'] = null;
        }

        $payload['marks_type'] = $payload['marks_type'] ?? 'single';
        $payload['marks_list'] = $payload['marks_list'] ?? null;
        $payload['allows_multiple'] = $allowsMultiple;

        return $payload;
    }
}
