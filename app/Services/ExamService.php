<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Support\UniqueOrgSlug;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(protected GalleryService $gallery) {}

    public function getByOrganization(int $orgId, int $perPage = 20): LengthAwarePaginator
    {
        return Exam::where('organization_id', $orgId)
            ->with(['category', 'createdBy'])
            ->withCount('questions')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Normalise form-request data into model-ready column names.
     */
    public function prepareData(array $data): array
    {
        // Map wizard field aliases → model columns
        if (isset($data['exam_duration_minutes'])) {
            $data['duration'] = (int) $data['exam_duration_minutes'];
            unset($data['exam_duration_minutes']);
        }
        if (array_key_exists('schedule_start_at', $data)) {
            $data['scheduled_start'] = $data['schedule_start_at'] ?: null;
            unset($data['schedule_start_at']);
        }
        if (array_key_exists('schedule_end_at', $data)) {
            $data['scheduled_end'] = $data['schedule_end_at'] ?: null;
            unset($data['schedule_end_at']);
        }
        if (array_key_exists('attempt_limit_type', $data) && $data['attempt_limit_type'] === 'fixed_count') {
            $data['attempt_limit_type'] = 'fixed';
        }
        if (isset($data['attempt_limit_count'])) {
            $data['max_attempts'] = (int) $data['attempt_limit_count'];
            unset($data['attempt_limit_count']);
        }
        if (($data['attempt_limit_type'] ?? null) === 'unlimited') {
            $data['max_attempts'] = 0;
        } elseif (($data['attempt_limit_type'] ?? null) === 'once') {
            $data['max_attempts'] = 1;
        }

        // Map exam_category_id (the create form uses this name) → category_id
        if (array_key_exists('exam_category_id', $data)) {
            $data['category_id'] = $data['exam_category_id'] ?: null;
            unset($data['exam_category_id']);
        }

        foreach ([
            'exam_format',
            'selected_categories',
            'predefined_instruction_rules',
            'tags',
            'question_marks_filter',
            'imported_candidates',
            'manual_candidate_emails',
            'free_imported_candidates',
            'free_manual_candidate_emails',
            'extra_questions_categories',
            'extra_questions_allocations',
            'extra_marks_allocations',
            'category_question_rules',
            'selected_discounts',
            'custom_discounts',
            'question_ids',
        ] as $jsonField) {
            if (! array_key_exists($jsonField, $data)) {
                continue;
            }
            if (is_string($data[$jsonField])) {
                $decoded = json_decode($data[$jsonField], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$jsonField] = $decoded;
                } elseif ($jsonField === 'exam_format' && filled($data[$jsonField])) {
                    $data[$jsonField] = [$data[$jsonField]];
                }
            }
        }

        if (isset($data['total_marks'], $data['passing_marks']) && ! array_key_exists('pass_percentage', $data)) {
            $totalMarks = max(0, (int) $data['total_marks']);
            $passingMarks = max(0, (int) $data['passing_marks']);
            $data['pass_percentage'] = $totalMarks > 0
                ? round(($passingMarks / $totalMarks) * 100, 2)
                : 0;
        }

        if (array_key_exists('use_question_pool', $data)) {
            $data['use_question_pool'] = (bool) $data['use_question_pool'];
            if ($data['use_question_pool']) {
                $data['fixed_questions'] = false;
                $data['maximum_questions'] = max(
                    (int) ($data['total_questions'] ?? 0) + 1,
                    (int) ($data['maximum_questions'] ?? 0)
                );
            } else {
                $data['maximum_questions'] = null;
            }
        }

        if (array_key_exists('fixed_questions', $data)) {
            $data['fixed_questions'] = (bool) $data['fixed_questions'];
        }
        $data['fixed_paper_set'] = (bool) ($data['fixed_paper_set'] ?? false);
        $data['shuffle_questions'] = (bool) ($data['shuffle_questions'] ?? false);
        $data['shuffle_categories'] = (bool) ($data['shuffle_categories'] ?? false);
        $data['fix_category_questions'] = (bool) ($data['fix_category_questions'] ?? false);
        $data['fix_category_marks'] = (bool) ($data['fix_category_marks'] ?? false);
        $data['enable_negative_marking'] = (bool) ($data['enable_negative_marking'] ?? false);

        if (! $data['fix_category_questions']) {
            $data['extra_questions_allocations'] = [];
            $data['extra_questions_categories'] = [];
        }

        if (! $data['fix_category_marks']) {
            $data['extra_marks_allocations'] = [];
        }

        if (! $data['fixed_paper_set']) {
            $data['paper_sets'] = 1;
        } else {
            $data['paper_sets'] = max(1, (int) ($data['paper_sets'] ?? 1));
        }

        if ($data['enable_negative_marking']) {
            $type = $data['negative_marking_type'] ?? null;
            $allowedTypes = ['25', '33.33', '50', '100'];
            if (! in_array((string) $type, $allowedTypes, true)) {
                $data['negative_marking_type'] = '25';
            }
            if (! array_key_exists('negative_mark_per_question', $data) || $data['negative_mark_per_question'] === null) {
                $data['negative_mark_per_question'] = 0;
            }
        } else {
            $data['negative_marking_type'] = null;
            $data['negative_mark_per_question'] = 0;
        }

        $data['ai_generated'] = (bool) ($data['ai_generated'] ?? false);
        $data['ai_improve'] = (bool) ($data['ai_improve'] ?? false);

        // Strip helper / UI-only keys
        unset(
            $data['_token'],
            $data['_method'],
            $data['free_candidate_excel_file'],
            $data['candidate_excel_file']
        );

        return app(GalleryService::class)->sanitizeHtmlFields($data, [
            'description',
            'instructions',
        ]);
    }

    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $data = $this->prepareData($data);

            $ids = $this->resolvePersistedQuestionIds($data);
            unset($data['question_ids']);

            $selectedCats = $this->normalizeCategoryIds($data['selected_categories'] ?? []);
            $data['selected_categories'] = $selectedCats;

            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? 'draft';
            $this->applyUniqueSlug($data, (int) $data['organization_id'], null, (string) ($data['title'] ?? ''));

            $exam = Exam::create($data);
            $this->syncQuestions($exam, $ids);
            $exam->selectedQuestionCategories()->sync($selectedCats);
            $this->syncGalleryMedia($exam);

            return $exam->fresh(['questions']);
        });
    }

    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $data = $this->prepareData($data);

            $hasQuestionIds = array_key_exists('question_ids', $data);
            $ids = $hasQuestionIds ? $this->resolvePersistedQuestionIds($data) : null;
            unset($data['question_ids']);

            $selectedCats = null;
            if (array_key_exists('selected_categories', $data)) {
                $selectedCats = $this->normalizeCategoryIds($data['selected_categories'] ?? []);
                $data['selected_categories'] = $selectedCats;
            }

            if (array_key_exists('slug', $data) || array_key_exists('title', $data) || empty($exam->slug)) {
                $this->applyUniqueSlug(
                    $data,
                    (int) $exam->organization_id,
                    (int) $exam->id,
                    (string) ($data['title'] ?? $exam->title),
                );
            }

            $exam->update($data);

            if ($hasQuestionIds) {
                $this->syncQuestions($exam, $ids ?? []);
            }

            if (is_array($selectedCats)) {
                $exam->selectedQuestionCategories()->sync($selectedCats);
            }

            $this->syncGalleryMedia($exam->fresh());

            return $exam->fresh(['questions']);
        });
    }

    /**
     * Fixed / Pool modes persist IDs. Dynamic mode (both off) clears exam_question.
     *
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    protected function resolvePersistedQuestionIds(array $data): array
    {
        $usePool = (bool) ($data['use_question_pool'] ?? false);
        $fixedQuestions = (bool) ($data['fixed_questions'] ?? false);
        $rawIds = $data['question_ids'] ?? [];

        if (! $usePool && ! $fixedQuestions) {
            return [];
        }

        if (! is_array($rawIds)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $rawIds), static fn (int $id) => $id > 0)));
    }

    /**
     * @param  mixed  $categories
     * @return list<int|string>
     */
    protected function normalizeCategoryIds(mixed $categories): array
    {
        if (! is_array($categories)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($id) => is_numeric($id) ? (int) $id : trim((string) $id),
            $categories
        ), static fn ($id) => $id !== '' && $id !== null && $id !== 0));
    }

    public function syncQuestions(Exam $exam, array $questionIds): void
    {
        $sync = [];
        foreach (array_values($questionIds) as $i => $id) {
            $qid = (int) $id;
            if ($qid > 0) {
                $sync[$qid] = [
                    'sort_order' => $i,
                    'status' => 'active',
                ];
            }
        }
        $exam->questions()->sync($sync);
    }

    public function publish(Exam $exam): Exam
    {
        $exam->update(['status' => 'published']);

        return $exam->fresh();
    }

    public function delete(Exam $exam): bool
    {
        $this->gallery->purgeForModel($exam);

        return (bool) $exam->delete();
    }

    protected function syncGalleryMedia(Exam $exam): void
    {
        $this->gallery->syncForModel($exam, [
            $exam->description,
            $exam->instructions,
        ], (int) $exam->organization_id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyUniqueSlug(array &$data, int $orgId, ?int $ignoreId, string $fallback): void
    {
        $source = trim((string) ($data['slug'] ?? ''));
        if ($source === '') {
            $source = $fallback;
        }

        $data['slug'] = UniqueOrgSlug::forModel(Exam::class, $source, $orgId, $ignoreId);
    }

    public function getStats(int $orgId): array
    {
        return [
            'total' => Exam::where('organization_id', $orgId)->count(),
            'published' => Exam::where('organization_id', $orgId)->where('status', 'published')->count(),
            'draft' => Exam::where('organization_id', $orgId)->where('status', 'draft')->count(),
        ];
    }

    public function getAttemptStats(Exam $exam): array
    {
        $attempts = ExamAttempt::where('exam_id', $exam->id);

        return [
            'total' => $attempts->count(),
            'passed' => (clone $attempts)->where('passed', true)->count(),
            'failed' => (clone $attempts)->where('passed', false)->count(),
            'avg_score' => (clone $attempts)->avg('score') ?? 0,
        ];
    }
}
