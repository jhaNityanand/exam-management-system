<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamCategory;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * Org-scoped workspace stats for the current single-org admin dashboard.
     */
    public function workspaceStats(int $orgId): array
    {
        $topCategories = QuestionCategory::query()
            ->forOrg($orgId)
            ->withCount('questions')
            ->orderByDesc('questions_count')
            ->limit(8)
            ->get(['id', 'name']);

        $attemptDays = collect(range(6, 0))->map(function (int $daysAgo) use ($orgId) {
            $day = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $day->format('D'),
                'count' => ExamAttempt::query()
                    ->whereDate('created_at', $day)
                    ->whereHas('exam', fn ($q) => $q->where('organization_id', $orgId))
                    ->count(),
            ];
        });

        return [
            'total_questions' => Question::query()->forOrg($orgId)->count(),
            'total_categories' => QuestionCategory::query()->forOrg($orgId)->count(),
            'total_exam_categories' => ExamCategory::query()->forOrg($orgId)->count(),
            'total_members' => User::query()
                ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId))
                ->count(),
            'total_exams' => Exam::query()->forOrg($orgId)->count(),
            'active_exams' => Exam::query()
                ->forOrg($orgId)
                ->whereIn('status', ['active', 'published'])
                ->count(),
            'draft_exams' => Exam::query()->forOrg($orgId)->where('status', 'draft')->count(),
            'published_exams' => Exam::query()->forOrg($orgId)->where('status', 'published')->count(),
            'recent_members' => User::query()
                ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId))
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'created_at']),
            'recent_exams' => Exam::query()
                ->forOrg($orgId)
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'status', 'duration', 'pass_percentage', 'updated_at']),
            'category_chart' => [
                'labels' => $topCategories->pluck('name')->all(),
                'values' => $topCategories->pluck('questions_count')->all(),
            ],
            'attempts_chart' => [
                'labels' => $attemptDays->pluck('label')->all(),
                'values' => $attemptDays->pluck('count')->all(),
            ],
            'exam_chart' => [
                'labels' => ['Draft', 'Published', 'Active', 'Other'],
                'values' => [
                    Exam::query()->forOrg($orgId)->where('status', 'draft')->count(),
                    Exam::query()->forOrg($orgId)->where('status', 'published')->count(),
                    Exam::query()->forOrg($orgId)->where('status', 'active')->count(),
                    Exam::query()->forOrg($orgId)->whereNotIn('status', ['draft', 'published', 'active'])->count(),
                ],
            ],
        ];
    }

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
