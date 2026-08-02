<?php

namespace App\Services\Frontend;

use App\Models\Blog;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\Question;
use Illuminate\Support\Collection;

class DetailSidebarService
{
    public const LIMIT = 5;

    /** Pool size before shuffle so picks feel fresh without scanning the whole table. */
    public const POOL = 18;

    /**
     * @return array{eyebrow:string,title:string,view_all_url:?string,view_all_label:?string,items:Collection<int,array<string,mixed>>}
     */
    public function forBlog(Blog $blog, ?int $orgId): array
    {
        $items = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereKeyNot($blog->id)
            ->with(['category:id,name,slug', 'bannerImage', 'banners'])
            ->latest('published_at')
            ->limit(self::POOL)
            ->get(['id', 'title', 'slug', 'blog_category_id', 'banner_image_id', 'published_at'])
            ->shuffle()
            ->take(self::LIMIT)
            ->values()
            ->map(fn (Blog $item) => [
                'url' => route('frontend.blogs.show', $item->slug),
                'title' => $item->title,
                'image' => $item->bannerUrl(),
                'kicker' => $item->category?->name,
                'meta' => $item->published_at?->format('d M Y'),
            ]);

        return $this->payload(
            eyebrow: 'Keep reading',
            title: 'Latest blogs',
            items: $items,
            viewAllUrl: route('frontend.blogs.index'),
            viewAllLabel: 'View all blogs',
        );
    }

    /**
     * @return array{eyebrow:string,title:string,view_all_url:?string,view_all_label:?string,items:Collection<int,array<string,mixed>>}
     */
    public function forNews(News $news, ?int $orgId): array
    {
        $items = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereKeyNot($news->id)
            ->with(['category:id,name,slug', 'bannerImage', 'featuredImage'])
            ->latest('published_at')
            ->limit(self::POOL)
            ->get([
                'id',
                'title',
                'slug',
                'news_category_id',
                'banner_image_id',
                'featured_image_id',
                'published_at',
                'is_breaking',
                'is_trending',
            ])
            ->shuffle()
            ->take(self::LIMIT)
            ->values()
            ->map(function (News $item) {
                $kicker = $item->category?->name ?: 'News';
                if ($item->is_breaking) {
                    $kicker = 'Breaking · '.$kicker;
                }

                return [
                    'url' => route('frontend.news.show', $item->slug),
                    'title' => $item->title,
                    'image' => $item->featuredImageUrl() ?: $item->bannerUrl(),
                    'kicker' => $kicker,
                    'meta' => $item->published_at?->diffForHumans(),
                ];
            });

        return $this->payload(
            eyebrow: 'Stay updated',
            title: 'Latest news',
            items: $items,
            viewAllUrl: route('frontend.news.index'),
            viewAllLabel: 'View all news',
        );
    }

    /**
     * @return array{eyebrow:string,title:string,view_all_url:?string,view_all_label:?string,items:Collection<int,array<string,mixed>>}
     */
    public function forQuestion(Question $question, ?int $orgId): array
    {
        $items = Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereKeyNot($question->id)
            ->with(['category:id,name,slug', 'ogImage'])
            ->latest('id')
            ->limit(self::POOL)
            ->get(['id', 'title', 'body', 'slug', 'category_id', 'difficulty', 'type', 'og_image_id', 'allows_multiple'])
            ->shuffle()
            ->take(self::LIMIT)
            ->values()
            ->map(fn (Question $item) => [
                'url' => route('frontend.questions.show', $item->slug),
                'title' => $item->publicTitle(),
                'image' => $item->seoImageUrl(),
                'kicker' => $item->category?->name ?: $item->typeLabel(),
                'meta' => $item->difficultyLabel(),
            ]);

        return $this->payload(
            eyebrow: 'Practice more',
            title: 'Latest questions',
            items: $items,
            viewAllUrl: route('frontend.questions.index'),
            viewAllLabel: 'View all questions',
        );
    }

    /**
     * @return array{eyebrow:string,title:string,view_all_url:?string,view_all_label:?string,items:Collection<int,array<string,mixed>>}
     */
    public function forExam(Exam $exam, ?int $orgId): array
    {
        $items = Exam::query()
            ->publicCatalog()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereKeyNot($exam->id)
            ->with(['category:id,name,slug', 'bannerImage', 'ogImage'])
            ->latest('id')
            ->limit(self::POOL)
            ->get([
                'id',
                'title',
                'slug',
                'category_id',
                'banner_image_id',
                'og_image_id',
                'difficulty_level',
                'duration',
                'pricing_option',
                'exam_amount',
                'exam_currency',
            ])
            ->shuffle()
            ->take(self::LIMIT)
            ->values()
            ->map(function (Exam $item) {
                $metaParts = array_filter([
                    $item->difficulty_level ? ucfirst((string) $item->difficulty_level) : null,
                    $item->duration ? ((int) $item->duration).' min' : null,
                ]);

                return [
                    'url' => route('frontend.exams.show', $item->slug),
                    'title' => $item->title,
                    'image' => $item->seoImageUrl() ?: $item->bannerUrl(),
                    'kicker' => $item->category?->name,
                    'meta' => $metaParts !== [] ? implode(' · ', $metaParts) : null,
                ];
            });

        return $this->payload(
            eyebrow: 'Explore more',
            title: 'Latest exams',
            items: $items,
            viewAllUrl: route('frontend.exams.index'),
            viewAllLabel: 'View all exams',
        );
    }

    /**
     * @return array{eyebrow:string,title:string,view_all_url:?string,view_all_label:?string,items:Collection<int,array<string,mixed>>}
     */
    public function forCategory(ExamCategory $category, ?int $orgId): array
    {
        $categoryIds = collect([$category->id])
            ->merge($category->children?->pluck('id') ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $items = Exam::query()
            ->publicCatalog()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->when(
                $categoryIds !== [],
                fn ($q) => $q->whereIn('category_id', $categoryIds),
                fn ($q) => $q->where('category_id', $category->id)
            )
            ->with(['category:id,name,slug', 'bannerImage', 'ogImage'])
            ->latest('id')
            ->limit(self::POOL)
            ->get([
                'id',
                'title',
                'slug',
                'category_id',
                'banner_image_id',
                'og_image_id',
                'difficulty_level',
                'duration',
            ])
            ->shuffle()
            ->take(self::LIMIT)
            ->values()
            ->map(function (Exam $item) {
                $metaParts = array_filter([
                    $item->difficulty_level ? ucfirst((string) $item->difficulty_level) : null,
                    $item->duration ? ((int) $item->duration).' min' : null,
                ]);

                return [
                    'url' => route('frontend.exams.show', $item->slug),
                    'title' => $item->title,
                    'image' => $item->seoImageUrl() ?: $item->bannerUrl(),
                    'kicker' => $item->category?->name ?: 'Exam',
                    'meta' => $metaParts !== [] ? implode(' · ', $metaParts) : null,
                ];
            });

        return $this->payload(
            eyebrow: $category->name,
            title: 'Exams in this category',
            items: $items,
            viewAllUrl: route('frontend.exams.index', ['category_id' => $category->id]),
            viewAllLabel: 'View all',
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array{eyebrow:string,title:string,view_all_url:?string,view_all_label:?string,items:Collection<int,array<string,mixed>>}
     */
    protected function payload(
        string $eyebrow,
        string $title,
        Collection $items,
        ?string $viewAllUrl,
        ?string $viewAllLabel,
    ): array {
        return [
            'eyebrow' => $eyebrow,
            'title' => $title,
            'view_all_url' => $viewAllUrl,
            'view_all_label' => $viewAllLabel,
            'items' => $items,
        ];
    }
}
