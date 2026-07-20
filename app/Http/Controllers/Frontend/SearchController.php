<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $questions = collect();

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

            $questions = Question::query()
                ->publiclyVisible()
                ->when($orgId, fn ($q) => $q->forOrg($orgId))
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('body', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('reference', 'like', $like);
                })
                ->with(['category:id,name,slug'])
                ->latest('id')
                ->paginate((int) $request->input('per_page', 10), ['*'], 'question_page')
                ->withQueryString();
        }

        if ($this->wantsFrontendJson($request)) {
            return response()->json([
                'data' => [
                    'exams' => $term !== '' ? $this->mapExams($exams->getCollection()) : [],
                    'blogs' => $term !== '' ? $this->mapBlogs($blogs->getCollection()) : [],
                    'news' => $term !== '' ? $this->mapNews($news->getCollection()) : [],
                    'categories' => $this->mapCategories($categories),
                    'questions' => $term !== '' ? $this->mapQuestions($questions->getCollection()) : [],
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
                    'questions' => $term !== '' ? [
                        'current_page' => $questions->currentPage(),
                        'last_page' => $questions->lastPage(),
                        'total' => $questions->total(),
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
            'questions' => $questions,
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
                    'questions' => [],
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

        $questions = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('body', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'difficulty', 'category_id']);

        return response()->json([
            'data' => [
                'exams' => $this->mapExams($exams),
                'blogs' => $this->mapBlogs($blogs),
                'news' => $this->mapNews($news),
                'categories' => $this->mapCategories($categories),
                'questions' => $this->mapQuestions($questions),
            ],
        ]);
    }

    /**
     * @param  Collection<int, Exam>  $items
     * @return list<array<string, mixed>>
     */
    protected function mapExams(Collection $items): array
    {
        return $items->map(fn (Exam $exam) => [
            'id' => $exam->id,
            'title' => $exam->title,
            'slug' => $exam->slug,
            'url' => route('frontend.exams.show', $exam),
            'href' => route('frontend.exams.show', $exam),
            'difficulty_level' => $exam->difficulty_level,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Blog>  $items
     * @return list<array<string, mixed>>
     */
    protected function mapBlogs(Collection $items): array
    {
        return $items->map(fn (Blog $blog) => [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'url' => route('frontend.blogs.show', $blog),
            'href' => route('frontend.blogs.show', $blog),
            'excerpt' => $blog->excerpt,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, News>  $items
     * @return list<array<string, mixed>>
     */
    protected function mapNews(Collection $items): array
    {
        return $items->map(fn (News $item) => [
            'id' => $item->id,
            'title' => $item->title,
            'slug' => $item->slug,
            'url' => route('frontend.news.show', $item),
            'href' => route('frontend.news.show', $item),
            'excerpt' => $item->excerpt,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, ExamCategory>  $items
     * @return list<array<string, mixed>>
     */
    protected function mapCategories(Collection $items): array
    {
        return $items->map(fn (ExamCategory $category) => [
            'id' => $category->id,
            'name' => $category->name,
            'title' => $category->name,
            'slug' => $category->slug,
            'url' => route('frontend.categories.show', $category),
            'href' => route('frontend.categories.show', $category),
        ])->values()->all();
    }

    /**
     * @param  Collection<int, Question>  $items
     * @return list<array<string, mixed>>
     */
    protected function mapQuestions(Collection $items): array
    {
        return $items->map(fn (Question $question) => [
            'id' => $question->id,
            'title' => $question->publicTitle(),
            'slug' => $question->slug,
            'url' => route('frontend.questions.show', $question),
            'href' => route('frontend.questions.show', $question),
            'difficulty' => $question->difficulty,
        ])->values()->all();
    }
}
