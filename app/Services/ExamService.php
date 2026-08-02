<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamPart;
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
            ->with(['category', 'createdBy', 'parts'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Normalise exam-level form-request data into model-ready column names.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareData(array $data): array
    {
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

        if (array_key_exists('exam_category_id', $data)) {
            $data['category_id'] = $data['exam_category_id'] ?: null;
            unset($data['exam_category_id']);
        }

        foreach ([
            'exam_format',
            'tags',
            'imported_candidates',
            'manual_candidate_emails',
            'free_imported_candidates',
            'free_manual_candidate_emails',
            'selected_discounts',
            'custom_discounts',
            'predefined_instruction_rules',
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

        $data['enable_negative_marking'] = (bool) ($data['enable_negative_marking'] ?? false);
        if ($data['enable_negative_marking']) {
            $type = $data['negative_marking_type'] ?? null;
            $allowedTypes = ['25', '33.33', '50', '100'];
            if (! in_array((string) $type, $allowedTypes, true)) {
                $type = '25';
                $data['negative_marking_type'] = '25';
            }
            // Map UI percentage type → grading fraction (admin form only stores the type).
            $data['negative_mark_per_question'] = match ((string) $type) {
                '25' => 0.25,
                '33.33' => 0.3333,
                '50' => 0.50,
                '100' => 1.00,
                default => 0,
            };
        } else {
            $data['negative_marking_type'] = null;
            $data['negative_mark_per_question'] = 0;
        }

        $data['ai_generated'] = (bool) ($data['ai_generated'] ?? false);
        $data['ai_improve'] = (bool) ($data['ai_improve'] ?? false);

        unset(
            $data['_token'],
            $data['_method'],
            $data['free_candidate_excel_file'],
            $data['candidate_excel_file'],
            $data['parts'],
            $data['focus_violation_limit'], // stored on exam_proctoring_policies, not exams
        );

        return app(GalleryService::class)->sanitizeHtmlFields($data, [
            'description',
            'instructions',
        ]);
    }

    /**
     * @param  array<string, mixed>  $partData
     * @return array<string, mixed>
     */
    public function preparePartData(array $partData, int $sortOrder): array
    {
        foreach ([
            'selected_categories',
            'extra_questions_categories',
            'extra_questions_allocations',
            'extra_marks_allocations',
            'category_question_rules',
            'question_marks_filter',
            'question_ids',
        ] as $jsonField) {
            if (! array_key_exists($jsonField, $partData)) {
                continue;
            }
            if (is_string($partData[$jsonField])) {
                $decoded = json_decode($partData[$jsonField], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $partData[$jsonField] = $decoded;
                }
            }
        }

        $partData['sort_order'] = $sortOrder;
        $partData['is_default'] = (bool) ($partData['is_default'] ?? ($sortOrder === 0));
        $partData['name'] = trim((string) ($partData['name'] ?? '')) ?: ($sortOrder === 0 ? 'Default Part' : 'Part');
        $partData['total_questions'] = max(1, (int) ($partData['total_questions'] ?? 1));
        $partData['total_marks'] = max(1, (int) ($partData['total_marks'] ?? 1));
        unset($partData['duration']);

        $partData['use_question_pool'] = (bool) ($partData['use_question_pool'] ?? false);
        $partData['fixed_questions'] = (bool) ($partData['fixed_questions'] ?? false);
        $partData['fixed_paper_set'] = (bool) ($partData['fixed_paper_set'] ?? false);
        $partData['shuffle_questions'] = (bool) ($partData['shuffle_questions'] ?? false);
        $partData['shuffle_categories'] = (bool) ($partData['shuffle_categories'] ?? false);
        $partData['shuffle_options'] = (bool) ($partData['shuffle_options'] ?? false);
        $partData['fix_category_questions'] = (bool) ($partData['fix_category_questions'] ?? false);
        $partData['fix_category_marks'] = (bool) ($partData['fix_category_marks'] ?? false);
        $partData['fix_marks_each_question'] = (bool) ($partData['fix_marks_each_question'] ?? false);

        if ($partData['use_question_pool']) {
            $partData['fixed_questions'] = false;
            $partData['maximum_questions'] = max(
                (int) $partData['total_questions'] + 1,
                (int) ($partData['maximum_questions'] ?? 0)
            );
        } else {
            $partData['maximum_questions'] = null;
        }

        if (! $partData['fix_category_questions']) {
            $partData['extra_questions_allocations'] = [];
            $partData['extra_questions_categories'] = [];
        }

        if (! $partData['fix_category_marks']) {
            $partData['extra_marks_allocations'] = [];
        }

        if (! $partData['fixed_paper_set']) {
            $partData['paper_sets'] = 1;
        } else {
            $partData['paper_sets'] = max(1, (int) ($partData['paper_sets'] ?? 1));
        }

        $partData['selected_categories'] = $this->normalizeCategoryIds($partData['selected_categories'] ?? []);

        return $partData;
    }

    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $partsInput = $this->extractPartsInput($data);
            $policyOverrides = $this->extractPolicyOverrides($data);
            $data = $this->prepareData($data);

            $data['created_by'] = Auth::id();
            $data['status'] = $data['status'] ?? 'draft';
            $this->applyUniqueSlug($data, (int) $data['organization_id'], null, (string) ($data['title'] ?? ''));

            $exam = Exam::create($data);
            $this->syncParts($exam, $partsInput);
            $this->refreshExamAggregates($exam);
            $this->syncGalleryMedia($exam);
            app(\App\Services\CandidateExam\ExamRequirementResolver::class)
                ->syncPolicy($exam, null, $policyOverrides);

            return $exam->fresh(['parts.questions', 'proctoringPolicy']);
        });
    }

    public function update(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $partsInput = array_key_exists('parts', $data) || array_key_exists('parts', request()->all())
                ? $this->extractPartsInput($data)
                : null;

            $policyOverrides = $this->extractPolicyOverrides($data);
            $data = $this->prepareData($data);

            if (array_key_exists('slug', $data) || array_key_exists('title', $data) || empty($exam->slug)) {
                $this->applyUniqueSlug(
                    $data,
                    (int) $exam->organization_id,
                    (int) $exam->id,
                    (string) ($data['title'] ?? $exam->title),
                );
            }

            $exam->update($data);

            if (is_array($partsInput)) {
                $this->syncParts($exam, $partsInput);
            }

            $this->refreshExamAggregates($exam->fresh());
            $this->syncGalleryMedia($exam->fresh());
            app(\App\Services\CandidateExam\ExamRequirementResolver::class)
                ->syncPolicy($exam->fresh(), null, $policyOverrides);

            return $exam->fresh(['parts.questions', 'proctoringPolicy']);
        });
    }

    /**
     * Extract proctoring fields that live on exam_proctoring_policies (not exams).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function extractPolicyOverrides(array $data): ?array
    {
        if (! array_key_exists('focus_violation_limit', $data)) {
            return null;
        }

        return [
            'focus_violation_limit' => max(0, min(99, (int) $data['focus_violation_limit'])),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    protected function extractPartsInput(array $data): array
    {
        $parts = $data['parts'] ?? request()->input('parts', []);
        if (is_string($parts)) {
            $decoded = json_decode($parts, true);
            $parts = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (! is_array($parts) || $parts === []) {
            return [[
                'name' => 'Default Part',
                'is_default' => true,
                'total_questions' => 50,
                'total_marks' => 100,
                'selected_categories' => [],
                'question_marks_filter' => [1],
            ]];
        }

        return array_values($parts);
    }

    /**
     * Replace exam parts with the submitted set (dev-phase: full sync, no soft merge).
     *
     * @param  list<array<string, mixed>>  $partsInput
     */
    public function syncParts(Exam $exam, array $partsInput): void
    {
        $keptIds = [];

        foreach (array_values($partsInput) as $index => $rawPart) {
            if (! is_array($rawPart)) {
                continue;
            }

            $prepared = $this->preparePartData($rawPart, $index);
            $questionIds = $this->resolvePersistedQuestionIds($prepared);
            unset($prepared['question_ids'], $prepared['id'], $prepared['temp_key']);

            $partId = isset($rawPart['id']) && is_numeric($rawPart['id']) ? (int) $rawPart['id'] : null;
            $part = $partId
                ? $exam->parts()->whereKey($partId)->first()
                : null;

            if ($part) {
                $part->update($prepared);
            } else {
                $part = $exam->parts()->create($prepared);
            }

            $keptIds[] = $part->id;
            $this->syncPartQuestions($part, $questionIds);
            $part->selectedQuestionCategories()->sync($prepared['selected_categories'] ?? []);
        }

        $exam->parts()
            ->whereNotIn('id', $keptIds)
            ->each(function (ExamPart $part) {
                $part->questions()->detach();
                $part->selectedQuestionCategories()->detach();
                $part->delete();
            });
    }

    public function refreshExamAggregates(Exam $exam): void
    {
        $exam->load('parts');
        $totalQuestions = (int) $exam->parts->sum('total_questions');
        $totalMarks = (int) $exam->parts->sum('total_marks');
        $passingMarks = (int) ($exam->passing_marks ?? 0);

        $exam->forceFill([
            'total_questions' => $totalQuestions,
            'total_marks' => $totalMarks,
            'pass_percentage' => $totalMarks > 0
                ? round(($passingMarks / $totalMarks) * 100, 2)
                : 0,
        ])->save();
    }

    /**
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

    public function syncPartQuestions(ExamPart $part, array $questionIds): void
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
        $part->questions()->sync($sync);
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
