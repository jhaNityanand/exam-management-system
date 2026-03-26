<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Organization;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function adminStats(): array
    {
        return [
            'total_organizations' => Organization::count(),
            'total_users' => User::count(),
            'total_exams' => Exam::count(),
            'total_questions' => Question::count(),
            'recent_organizations' => Organization::latest()->limit(5)->get(),
            'recent_users' => User::latest()->limit(5)->get(),
            'exam_chart' => [
                'labels' => ['Draft', 'Published', 'Other'],
                'values' => [
                    Exam::where('status', 'draft')->count(),
                    Exam::where('status', 'published')->count(),
                    Exam::whereNotIn('status', ['draft', 'published'])->count(),
                ],
            ],
        ];
    }

    public function orgAdminStats(int $orgId): array
    {
        $draft = Exam::where('organization_id', $orgId)->where('status', 'draft')->count();
        $published = Exam::where('organization_id', $orgId)->where('status', 'published')->count();

        return [
            'total_members' => User::whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId))->count(),
            'total_exams' => Exam::where('organization_id', $orgId)->count(),
            'published_exams' => $published,
            'total_questions' => Question::where('organization_id', $orgId)->count(),
            'recent_exams' => Exam::where('organization_id', $orgId)->latest()->limit(5)->get(),
            'exam_chart' => [
                'labels' => ['Draft', 'Published'],
                'values' => [$draft, $published],
            ],
        ];
    }

    public function editorStats(int $orgId): array
    {
        $userId = Auth::id();

        return [
            'my_questions' => Question::where('organization_id', $orgId)->where('created_by', $userId)->count(),
            'my_exams' => Exam::where('organization_id', $orgId)->where('created_by', $userId)->count(),
            'total_questions' => Question::where('organization_id', $orgId)->count(),
            'draft_exams' => Exam::where('organization_id', $orgId)->where('status', 'draft')->count(),
            'question_difficulty' => [
                'labels' => ['Easy', 'Medium', 'Hard'],
                'values' => [
                    Question::where('organization_id', $orgId)->where('difficulty', 'easy')->count(),
                    Question::where('organization_id', $orgId)->where('difficulty', 'medium')->count(),
                    Question::where('organization_id', $orgId)->where('difficulty', 'hard')->count(),
                ],
            ],
        ];
    }

    public function viewerStats(int $orgId): array
    {
        $userId = Auth::id();

        return [
            'available_exams' => Exam::where('organization_id', $orgId)->where('status', 'published')->count(),
            'my_attempts' => ExamAttempt::where('user_id', $userId)->count(),
            'passed_attempts' => ExamAttempt::where('user_id', $userId)->where('passed', true)->count(),
            'recent_attempts' => ExamAttempt::where('user_id', $userId)->latest()->limit(5)->with('exam')->get(),
        ];
    }
}
