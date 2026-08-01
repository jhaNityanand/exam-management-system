<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamCategory;
use App\Models\ExamPayment;
use App\Models\Gallery;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Support\OrganizationRoles;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * Org-scoped workspace stats for the current single-org admin dashboard.
     */
    public function workspaceStats(int $orgId): array
    {
        $driver = DB::connection()->getDriverName();
        $dayExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $topCategories = QuestionCategory::query()
            ->forOrg($orgId)
            ->withCount('questions')
            ->orderByDesc('questions_count')
            ->limit(8)
            ->get(['id', 'name']);

        $attemptFrom = Carbon::today()->subDays(6)->startOfDay();
        $attemptTo = Carbon::today()->endOfDay();

        $countsByDay = ExamAttempt::query()
            ->whereBetween('created_at', [$attemptFrom, $attemptTo])
            ->whereHas('exam', fn ($q) => $q->where('organization_id', $orgId))
            ->selectRaw("{$dayExpression} as day, COUNT(*) as aggregate")
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $attemptDays = collect(range(6, 0))->map(function (int $daysAgo) use ($countsByDay) {
            $day = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $day->format('D'),
                'count' => (int) ($countsByDay[$day->toDateString()] ?? 0),
            ];
        });

        $examStatusCounts = [
            'draft' => Exam::query()->forOrg($orgId)->where('status', 'draft')->count(),
            'published' => Exam::query()->forOrg($orgId)->where('status', 'published')->count(),
            'active' => Exam::query()->forOrg($orgId)->where('status', 'active')->count(),
            'other' => Exam::query()->forOrg($orgId)->whereNotIn('status', ['draft', 'published', 'active'])->count(),
        ];

        $totalBlogs = Blog::query()->forOrg($orgId)->count();
        $totalNews = News::query()->forOrg($orgId)->count();
        $totalQuestions = Question::query()->forOrg($orgId)->count();
        $totalExams = Exam::query()->forOrg($orgId)->count();
        $totalGallery = Gallery::query()->forOrg($orgId)->where(function ($q) {
            $q->where('kind', 'image')->orWhere('mime_type', 'like', 'image/%');
        })->count();

        $roleCounts = DB::table('user_organizations')
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->select('role', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $candidateCount = (int) ($roleCounts[OrganizationRoles::CANDIDATE] ?? 0)
            + (int) ($roleCounts[OrganizationRoles::VIEWER] ?? 0);
        $orgMemberCount = (int) ($roleCounts[OrganizationRoles::ADMIN] ?? 0)
            + (int) ($roleCounts[OrganizationRoles::ORG_ADMIN] ?? 0)
            + (int) ($roleCounts[OrganizationRoles::EDITOR] ?? 0);

        $growthMonths = collect(range(5, 0))->map(fn (int $ago) => Carbon::today()->startOfMonth()->subMonths($ago));
        $monthKeys = $growthMonths->map(fn (Carbon $m) => $m->format('Y-m'));

        $contentGrowth = [
            'questions' => $this->monthlyCounts(Question::query()->forOrg($orgId), $monthExpression, $monthKeys),
            'blogs' => $this->monthlyCounts(Blog::query()->forOrg($orgId), $monthExpression, $monthKeys),
            'news' => $this->monthlyCounts(News::query()->forOrg($orgId), $monthExpression, $monthKeys),
        ];

        $candidateGrowth = $this->monthlyCounts(
            DB::table('user_organizations')
                ->where('organization_id', $orgId)
                ->whereIn('role', OrganizationRoles::candidateRoles()),
            $monthExpression,
            $monthKeys,
            'created_at'
        );

        return [
            'total_questions' => $totalQuestions,
            'total_question_categories' => QuestionCategory::query()->forOrg($orgId)->count(),
            'total_exams' => $totalExams,
            'total_exam_categories' => ExamCategory::query()->forOrg($orgId)->count(),
            'total_blogs' => $totalBlogs,
            'total_blog_categories' => BlogCategory::query()->forOrg($orgId)->count(),
            'total_news' => $totalNews,
            'total_news_categories' => NewsCategory::query()->forOrg($orgId)->count(),
            'total_gallery_images' => $totalGallery,
            'total_gallery_categories' => (int) Gallery::query()
                ->forOrg($orgId)
                ->whereNotNull('folder')
                ->where('folder', '!=', '')
                ->distinct()
                ->count('folder'),
            'total_candidates' => $candidateCount,
            'total_organization_members' => $orgMemberCount,
            'total_organizations' => Organization::query()->count(),
            'total_notifications' => 0,
            'total_transactions' => Schema::hasTable('exam_payments')
                ? ExamPayment::query()->where('organization_id', $orgId)->count()
                : 0,
            'active_exams' => $examStatusCounts['published'] + $examStatusCounts['active'],
            'charts' => [
                'questions_by_category' => [
                    'labels' => $topCategories->pluck('name')->all(),
                    'values' => $topCategories->pluck('questions_count')->all(),
                ],
                'exam_attempts' => [
                    'labels' => $attemptDays->pluck('label')->all(),
                    'values' => $attemptDays->pluck('count')->all(),
                ],
                'exams_by_status' => [
                    'labels' => ['Draft', 'Published', 'Active', 'Other'],
                    'values' => array_values($examStatusCounts),
                ],
                'blog_vs_news' => [
                    'labels' => ['Blogs', 'News'],
                    'values' => [$totalBlogs, $totalNews],
                ],
                'members_by_role' => [
                    'labels' => ['Admin', 'Org Admin', 'Candidate'],
                    'values' => [
                        (int) ($roleCounts[OrganizationRoles::ADMIN] ?? 0),
                        (int) ($roleCounts[OrganizationRoles::ORG_ADMIN] ?? 0),
                        $candidateCount,
                    ],
                ],
                'candidate_registrations' => [
                    'labels' => $growthMonths->map(fn (Carbon $m) => $m->format('M Y'))->all(),
                    'values' => $candidateGrowth,
                ],
                'monthly_content_growth' => [
                    'labels' => $growthMonths->map(fn (Carbon $m) => $m->format('M Y'))->all(),
                    'datasets' => [
                        ['label' => 'Questions', 'values' => $contentGrowth['questions']],
                        ['label' => 'Blogs', 'values' => $contentGrowth['blogs']],
                        ['label' => 'News', 'values' => $contentGrowth['news']],
                    ],
                ],
                'content_distribution' => [
                    'labels' => ['Questions', 'Exams', 'Blogs', 'News', 'Gallery'],
                    'values' => [$totalQuestions, $totalExams, $totalBlogs, $totalNews, $totalGallery],
                ],
            ],
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Support\Collection<int, string>  $monthKeys
     * @return list<int>
     */
    protected function monthlyCounts($query, string $monthExpression, $monthKeys, string $dateColumn = 'created_at'): array
    {
        $raw = (clone $query)
            ->where($dateColumn, '>=', Carbon::today()->startOfMonth()->subMonths(5)->startOfDay())
            ->selectRaw("{$monthExpression} as month_key, COUNT(*) as aggregate")
            ->groupBy('month_key')
            ->pluck('aggregate', 'month_key');

        return $monthKeys->map(fn (string $key) => (int) ($raw[$key] ?? 0))->values()->all();
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
