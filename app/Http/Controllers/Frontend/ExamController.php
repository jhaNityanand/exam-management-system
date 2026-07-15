<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Exam;
use App\Models\ExamCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $query = Exam::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug'])
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

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'title' => $query->orderBy('title'),
            'difficulty' => $query->orderBy('difficulty_level')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $exams = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedJson($exams);
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

    public function show(Exam $exam): View
    {
        $orgId = $this->organizationId();

        abort_unless(
            $exam->status === 'published'
                && ($orgId === null || (int) $exam->organization_id === $orgId),
            404
        );

        $exam->load(['category:id,name,slug,description']);

        $relatedExams = Exam::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $exam->id)
            ->when($exam->category_id, fn ($q) => $q->where('category_id', $exam->category_id))
            ->with(['category:id,name,slug'])
            ->latest('id')
            ->limit(4)
            ->get();

        return view('frontend.exam.show', [
            'exam' => $exam,
            'relatedExams' => $relatedExams,
        ]);
    }
}
