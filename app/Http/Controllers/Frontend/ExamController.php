<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamCategory;
use App\Services\CandidateExam\ExamEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    use RespondsWithFrontendJson;

    public function __construct(
        protected ExamEligibilityService $eligibility
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();
        $user = $request->user();

        $query = Exam::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug', 'bannerImage', 'ogImage'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('difficulty_level'), fn ($q) => $q->where('difficulty_level', $request->input('difficulty_level')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        if (! $user) {
            $query->where('visibility', 'public');
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                    ->orWhereHas('entitlements', function ($entitlements) use ($user) {
                        $entitlements->where('user_id', $user->id)
                            ->where('status', 'active')
                            ->where(function ($window) {
                                $window->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                            })
                            ->where(function ($window) {
                                $window->whereNull('valid_until')->orWhere('valid_until', '>', now());
                            });
                    });
            });
        }

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'title' => $query->orderBy('title'),
            'difficulty' => $query->orderBy('difficulty_level')->orderByDesc('id'),
            'duration' => $query->orderBy('duration')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $exams = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($exams, 'frontend.components.exam-card', 'exam');
        }

        $categories = ExamCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id']);

        return view('frontend.exam.index', [
            'exams' => $exams,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'difficulty_level', 'search', 'sort']),
        ]);
    }

    public function show(Request $request, Exam $exam): View
    {
        $orgId = $this->organizationId();
        $user = $request->user();

        abort_unless($exam->status === 'published', 404);
        if ($orgId !== null && (int) $exam->organization_id !== $orgId && ! ($user && $this->eligibility->canViewPublicDetail($exam, $user))) {
            abort(404);
        }
        abort_unless($this->eligibility->canViewPublicDetail($exam, $user), 404);

        $exam->load(['category:id,name,slug,description', 'ogImage', 'bannerImage', 'proctoringPolicy']);

        $evaluation = $user ? $this->eligibility->evaluate($exam, $user) : [
            'can_attempt' => false,
            'can_continue' => false,
            'requires_payment' => $this->eligibility->requiresPayment($exam),
            'has_entitlement' => false,
            'active_attempt_id' => null,
            'reasons' => ['Login required'],
            'attempts_used' => 0,
            'attempts_allowed' => $this->eligibility->allowedAttempts($exam),
        ];

        $previousAttempts = $user
            ? ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['submitted', 'expired', 'graded', 'abandoned'])
                ->latest('id')
                ->limit(10)
                ->get(['id', 'status', 'score', 'percentage', 'passed', 'submitted_at', 'started_at'])
            : collect();

        $relatedExams = Exam::query()
            ->publicCatalog()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $exam->id)
            ->when($exam->category_id, fn ($q) => $q->where('category_id', $exam->category_id))
            ->with(['category:id,name,slug'])
            ->latest('id')
            ->limit(4)
            ->get();

        return view('frontend.exam.show', [
            'exam' => $exam,
            'evaluation' => $evaluation,
            'previousAttempts' => $previousAttempts,
            'relatedExams' => $relatedExams,
        ]);
    }
}
