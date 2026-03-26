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

    public function create(array $data): Exam
    {
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
