<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

        $questions = $query->paginate((int) $request->input('per_page', 36))->withQueryString();

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

        $payload = $question->toPracticeDetailPayload();
        $relatedBlogs = $this->relatedBlogsForQuestion($question, $payload, $orgId);

        return view('frontend.questions.show', [
            'question' => $question,
            'payload' => $payload,
            'relatedBlogs' => $relatedBlogs,
        ]);
    }

    public function categories(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();

        $categories = QuestionCategory::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->withCount(['publicQuestions as questions_count'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        match ($request->input('sort', 'name')) {
            'name_desc' => $categories->orderByDesc('name'),
            'popular' => $categories->orderByDesc('questions_count')->orderBy('name'),
            default => $categories->orderBy('sort_order')->orderBy('name'),
        };

        $categories = $categories
            ->paginate((int) $request->input('per_page', 36))
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

        $query = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('category_id', $category->id)
            ->with(['category:id,name,slug'])
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->input('difficulty')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });

        match ($request->input('sort', 'latest')) {
            'oldest' => $query->oldest('id'),
            'title' => $query->orderBy('title')->orderByDesc('id'),
            'difficulty' => $query->orderBy('difficulty')->orderByDesc('id'),
            'popular' => $query->orderByDesc('view_count')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $questions = $query
            ->paginate((int) $request->input('per_page', 36))
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

    /**
     * @param  array{explanation: ?string, options: list<array{text: string, is_correct: bool}>}  $payload
     * @return Collection<int, Blog>
     */
    protected function relatedBlogsForQuestion(Question $question, array $payload, ?int $orgId): Collection
    {
        $stopWords = collect([
            'a', 'an', 'the', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'with', 'is', 'are', 'was', 'were',
            'be', 'by', 'as', 'at', 'from', 'that', 'this', 'it', 'its', 'into', 'about', 'true', 'false',
            'which', 'what', 'when', 'where', 'how', 'why', 'select', 'apply', 'all', 'following',
        ]);

        $raw = collect([
            $question->category?->name,
            $question->publicTitle(),
            strip_tags((string) $question->body),
            strip_tags((string) ($payload['explanation'] ?? '')),
            collect($payload['options'] ?? [])->where('is_correct', true)->pluck('text')->implode(' '),
            collect($question->public_tags ?? [])->map(fn ($tag) => is_array($tag) ? ($tag['name'] ?? '') : $tag)->implode(' '),
        ])->filter()->implode(' ');

        $keywords = collect(preg_split('/[^a-zA-Z0-9]+/', Str::lower($raw)) ?: [])
            ->map(fn ($word) => trim($word))
            ->filter(fn ($word) => strlen($word) >= 4 && ! $stopWords->contains($word))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(8)
            ->values();

        $query = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category:id,name,slug', 'bannerImage', 'banners', 'author:id,name']);

        if ($keywords->isNotEmpty()) {
            $query->where(function ($outer) use ($keywords) {
                foreach ($keywords as $word) {
                    $term = '%'.$word.'%';
                    $outer->orWhere('title', 'like', $term)
                        ->orWhere('excerpt', 'like', $term)
                        ->orWhere('content', 'like', $term)
                        ->orWhere('seo_keywords', 'like', $term);
                }
            });
        }

        $blogs = $query
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        if ($blogs->count() >= 3) {
            return $blogs;
        }

        $fallback = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereNotIn('id', $blogs->pluck('id'))
            ->with(['category:id,name,slug', 'bannerImage', 'banners', 'author:id,name'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3 - $blogs->count())
            ->get();

        return $blogs->concat($fallback)->take(3)->values();
    }
}
