<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $query = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug'])
            ->when($request->filled('category'), function ($q) use ($request, $orgId) {
                $slug = $request->string('category')->toString();
                $q->whereHas('category', function ($category) use ($slug, $orgId) {
                    $category->where('slug', $slug)->when($orgId, fn ($inner) => $inner->forOrg($orgId));
                });
            })
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->input('difficulty')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            });

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'title' => $query->orderBy('title')->orderByDesc('id'),
            'difficulty' => $query->orderBy('difficulty')->orderByDesc('id'),
            'popular' => $query->orderByDesc('view_count')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $questions = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($questions, 'frontend.components.question-card', 'question');
        }

        $categories = QuestionCategory::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->withCount(['publicQuestions as questions_count'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'icon', 'image_path']);

        return view('frontend.questions.index', [
            'questions' => $questions,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'difficulty', 'sort']),
        ]);
    }

    public function show(Request $request, Question $question): View
    {
        $orgId = $this->organizationId();

        abort_unless(
            $question->status === 'active'
                && $question->is_public
                && filled($question->slug)
                && ($orgId === null || (int) $question->organization_id === $orgId),
            404
        );

        $question->load(['category:id,name,slug,description']);
        $question->increment('view_count');

        $related = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '!=', $question->id)
            ->when($question->category_id, fn ($q) => $q->where('category_id', $question->category_id))
            ->with(['category:id,name,slug'])
            ->latest('id')
            ->limit(6)
            ->get();

        $previous = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '<', $question->id)
            ->orderByDesc('id')
            ->first(['id', 'title', 'slug', 'body']);

        $next = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('id', '>', $question->id)
            ->orderBy('id')
            ->first(['id', 'title', 'slug', 'body']);

        return view('frontend.questions.show', [
            'question' => $question,
            'payload' => $question->toPublicPayload(),
            'related' => $related,
            'previous' => $previous,
            'next' => $next,
        ]);
    }

    public function categories(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $categories = QuestionCategory::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->withCount(['publicQuestions as questions_count'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 24))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($categories, 'frontend.components.question-category-card', 'category');
        }

        return view('frontend.questions.categories', [
            'categories' => $categories,
        ]);
    }

    public function category(Request $request, string $slug): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $category = QuestionCategory::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('slug', $slug)
            ->firstOrFail();

        $questions = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('category_id', $category->id)
            ->with(['category:id,name,slug'])
            ->latest('id')
            ->paginate((int) $request->input('per_page', 12))
            ->withQueryString();

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($questions, 'frontend.components.question-card', 'question');
        }

        $category->loadCount(['publicQuestions as questions_count']);

        return view('frontend.questions.category', [
            'category' => $category,
            'questions' => $questions,
        ]);
    }
}
