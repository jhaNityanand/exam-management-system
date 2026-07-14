<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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
        if (isset($data['attempt_limit_count'])) {
            $data['max_attempts'] = (int) $data['attempt_limit_count'];
            unset($data['attempt_limit_count']);
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

        if (isset($data['total_marks'], $data['passing_marks'])) {
            $totalMarks = max(0, (int) $data['total_marks']);
            $passingMarks = max(0, (int) $data['passing_marks']);
            $data['pass_percentage'] = $totalMarks > 0
                ? round(($passingMarks / $totalMarks) * 100, 2)
                : ($data['pass_percentage'] ?? 0);
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
        $data = $this->prepareData($data);

        $ids = $data['question_ids'] ?? [];
        unset($data['question_ids']);

        $selectedCats = $data['selected_categories'] ?? [];

        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        $exam = Exam::create($data);
        $this->syncQuestions($exam, is_array($ids) ? $ids : []);

        if (! empty($selectedCats) && is_array($selectedCats)) {
            $exam->selectedQuestionCategories()->sync($selectedCats);
        }

        $this->syncGalleryMedia($exam);

        return $exam->fresh(['questions']);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $data = $this->prepareData($data);

        $ids = $data['question_ids'] ?? null;
        unset($data['question_ids']);

        $selectedCats = $data['selected_categories'] ?? null;

        $exam->update($data);

        if (is_array($ids)) {
            $this->syncQuestions($exam, $ids);
        }

        if (is_array($selectedCats)) {
            $exam->selectedQuestionCategories()->sync($selectedCats);
        }

        $this->syncGalleryMedia($exam->fresh());

        return $exam->fresh(['questions']);
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
