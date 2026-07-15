<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = $this->organizationId();
        $term = $request->string('q')->trim()->toString();

        $exams = collect();
        $blogs = collect();
        $news = collect();
        $categories = collect();

        if ($term !== '') {
            $like = '%'.$term.'%';

            $exams = Exam::query()
                ->published()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                })
                ->with(['category:id,name,slug'])
                ->latest('id')
                ->paginate((int) $request->input('per_page', 10), ['*'], 'exam_page')
                ->withQueryString();

            $blogs = Blog::query()
                ->published()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                })
                ->with(['category:id,name,slug', 'bannerImage'])
                ->latest('published_at')
                ->paginate((int) $request->input('per_page', 10), ['*'], 'blog_page')
                ->withQueryString();

            $news = News::query()
                ->published()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('short_description', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                })
                ->with(['category:id,name,slug', 'bannerImage', 'featuredImage'])
                ->latest('published_at')
                ->paginate((int) $request->input('per_page', 10), ['*'], 'news_page')
                ->withQueryString();

            $categories = ExamCategory::query()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where('status', 'active')
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'slug', 'description']);
        }

        if ($this->wantsFrontendJson($request)) {
            return response()->json([
                'data' => [
                    'exams' => $term !== '' ? $exams->items() : [],
                    'blogs' => $term !== '' ? $blogs->items() : [],
                    'news' => $term !== '' ? $news->items() : [],
                    'categories' => $categories,
                ],
                'meta' => [
                    'q' => $term,
                    'exams' => $term !== '' ? [
                        'current_page' => $exams->currentPage(),
                        'last_page' => $exams->lastPage(),
                        'total' => $exams->total(),
                    ] : ['total' => 0],
                    'blogs' => $term !== '' ? [
                        'current_page' => $blogs->currentPage(),
                        'last_page' => $blogs->lastPage(),
                        'total' => $blogs->total(),
                    ] : ['total' => 0],
                    'news' => $term !== '' ? [
                        'current_page' => $news->currentPage(),
                        'last_page' => $news->lastPage(),
                        'total' => $news->total(),
                    ] : ['total' => 0],
                ],
            ]);
        }

        return view('frontend.search.index', [
            'q' => $term,
            'exams' => $exams,
            'blogs' => $blogs,
            'news' => $news,
            'categories' => $categories,
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $orgId = $this->organizationId();
        $term = $request->string('q')->trim()->toString();

        if ($term === '') {
            return response()->json([
                'data' => [
                    'exams' => [],
                    'blogs' => [],
                    'news' => [],
                    'categories' => [],
                ],
            ]);
        }

        $like = '%'.$term.'%';

        $exams = Exam::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'difficulty_level', 'category_id']);

        $blogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        $news = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'is_breaking', 'is_trending']);

        $categories = ExamCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'data' => [
                'exams' => $exams,
                'blogs' => $blogs,
                'news' => $news,
                'categories' => $categories,
            ],
        ]);
    }
}
