<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ExamService
{
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

        // Strip helper / UI-only keys
        unset($data['_token'], $data['_method']);

        return $data;
    }

    public function create(array $data): Exam
    {
        $data = $this->prepareData($data);

        $ids = $data['question_ids'] ?? [];
        unset($data['question_ids']);

        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'draft';

        $exam = Exam::create($data);
        $this->syncQuestions($exam, $ids);

        return $exam->fresh(['questions']);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $data = $this->prepareData($data);

        $ids = $data['question_ids'] ?? null;
        unset($data['question_ids']);

        $exam->update($data);

        if (is_array($ids)) {
            $this->syncQuestions($exam, $ids);
        }

        return $exam->fresh(['questions']);
    }

    public function syncQuestions(Exam $exam, array $questionIds): void
    {
        $sync = [];
        foreach (array_values($questionIds) as $i => $id) {
            $sync[(int) $id] = [
                'sort_order' => $i,
                'status' => 'active',
            ];
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
        return $exam->delete();
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
