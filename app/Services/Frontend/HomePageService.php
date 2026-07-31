<?php

namespace App\Services\Frontend;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Cms\Faq;
use App\Models\Cms\HeroBanner;
use App\Models\Cms\Partner;
use App\Models\Cms\Testimonial;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomePageService
{
    public function __construct(protected SiteCmsService $cms) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $orgId = $this->cms->organizationId();
        $sections = $this->cms->homeSections($orgId);
        $featuredExams = $this->featuredExams($orgId, 12);
        $randomQuestions = $this->randomQuestions($orgId, 12);
        $randomBlogs = $this->randomBlogsWithBanner($orgId, 9);
        $randomNews = $this->randomNewsWithBanner($orgId, 9);

        return [
            'sections' => $sections,
            'banners' => $this->banners($orgId),
            'heroSlides' => $this->heroSlides($orgId, $featuredExams, $randomBlogs, $randomNews, $randomQuestions),
            'stats' => $this->stats($orgId),
            'featuredExams' => $featuredExams,
            'upcomingExams' => $this->upcomingExams($orgId),
            'categories' => $this->categories($orgId, 16),
            'examCategories' => $this->examCategories($orgId),
            'blogCategories' => $this->blogCategories($orgId),
            'newsCategories' => $this->newsCategories($orgId),
            'questionCategories' => $this->questionCategories($orgId),
            'featuredBlogs' => $randomBlogs,
            'latestBlogs' => $randomBlogs,
            'randomQuestions' => $randomQuestions,
            'breakingNews' => $this->news($orgId, breaking: true),
            'trendingNews' => $this->news($orgId, trending: true),
            'latestNews' => $randomNews,
            'testimonials' => $this->testimonials($orgId, 12),
            'faqs' => $this->faqs($orgId),
            'partners' => $this->partners($orgId),
            'newsletter' => [
                'title' => $this->cms->setting('newsletter.title', 'Stay Exam-Ready Every Week'),
                'subtitle' => $this->cms->setting(
                    'newsletter.subtitle',
                    'Get curated practice tips, new exams, and career-ready updates delivered to your inbox.'
                ),
                'cta' => $this->cms->setting('newsletter.cta', 'Subscribe'),
                'benefits' => [
                    'Weekly exam alerts',
                    'Practice tips from mentors',
                    'New blogs & news digests',
                ],
            ],
            'cta' => [
                'title' => $this->cms->setting('cta.title', 'Ready to start your next exam?'),
                'subtitle' => $this->cms->setting(
                    'cta.subtitle',
                    'Practice with structured mock tests, track scores, and learn with blogs & news built for exam aspirants.'
                ),
                'primary_label' => $this->cms->setting('cta.primary_label', 'Browse Exams'),
                'primary_url' => $this->cms->setting('cta.primary_url', '/exams'),
                'secondary_label' => $this->cms->setting('cta.secondary_label', 'Create free account'),
                'secondary_url' => $this->cms->setting('cta.secondary_url', '/register'),
            ],
        ];
    }

    /**
     * @return Collection<int, HeroBanner>
     */
    public function banners(?int $orgId = null): Collection
    {
        return HeroBanner::query()
            ->active()
            ->ordered()
            ->with(['image', 'mobileImage'])
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->get();
    }

    /**
     * Build dynamic hero slides from CMS banners and content modules.
     *
     * @return list<array<string, mixed>>
     */
    public function heroSlides(
        ?int $orgId,
        Collection $exams,
        Collection $blogs,
        Collection $news,
        Collection $questions
    ): array {
        $slides = [];

        foreach ($this->banners($orgId) as $banner) {
            $slides[] = [
                'badge' => $banner->badge_text,
                'title' => $banner->title,
                'description' => $banner->description ?: $banner->subtitle,
                'cta_label' => $banner->primary_cta_label ?: 'Explore',
                'cta_url' => $banner->primary_cta_url ?: route('frontend.exams.index'),
                'secondary_label' => $banner->secondary_cta_label,
                'secondary_url' => $banner->secondary_cta_url,
                'image' => $banner->image->file_url ?? null,
                'mobile_image' => $banner->mobileImage->file_url ?? null,
                'illustration' => asset('frontend/images/banner.svg'),
                'show_search' => (bool) $banner->show_search,
            ];
        }

        if ($exams->isNotEmpty()) {
            $exam = $exams->first();
            $slides[] = [
                'badge' => 'Featured Exam',
                'title' => $exam->title,
                'description' => Str::limit(strip_tags((string) $exam->description), 140),
                'cta_label' => 'Attempt Exam',
                'cta_url' => route('frontend.exams.show', $exam->slug),
                'illustration' => asset('frontend/images/exams.svg'),
                'image' => null,
            ];
        }

        if ($blogs->isNotEmpty()) {
            $blog = $blogs->first();
            $slides[] = [
                'badge' => 'Featured Blog',
                'title' => $blog->title,
                'description' => Str::limit(strip_tags((string) ($blog->excerpt ?: $blog->content)), 140),
                'cta_label' => 'Read Blog',
                'cta_url' => route('frontend.blogs.show', $blog->slug),
                'illustration' => asset('frontend/images/blogs.svg'),
                'image' => $blog->bannerUrl(),
            ];
        }

        if ($news->isNotEmpty()) {
            $item = $news->first();
            $slides[] = [
                'badge' => 'Latest News',
                'title' => $item->title,
                'description' => Str::limit(strip_tags((string) ($item->excerpt ?: $item->content)), 140),
                'cta_label' => 'Read News',
                'cta_url' => route('frontend.news.show', $item->slug),
                'illustration' => asset('frontend/images/news.svg'),
                'image' => $item->bannerUrl(),
            ];
        }

        if ($questions->isNotEmpty()) {
            $question = $questions->first();
            $slides[] = [
                'badge' => 'Practice Question',
                'title' => method_exists($question, 'publicTitle') ? $question->publicTitle() : Str::limit(strip_tags((string) $question->body), 80),
                'description' => 'Sharpen your concepts with curated practice questions across categories.',
                'cta_label' => 'View Question',
                'cta_url' => route('frontend.questions.show', $question),
                'illustration' => asset('frontend/images/questions.svg'),
            ];
        }

        $slides[] = [
            'badge' => 'Browse Categories',
            'title' => 'Explore topics that match your goals',
            'description' => 'Find exams, blogs, news, and questions organized by category.',
            'cta_label' => 'View Categories',
            'cta_url' => route('frontend.categories.index'),
            'illustration' => asset('frontend/images/categories.svg'),
        ];

        if ($slides === []) {
            $siteName = $this->cms->setting('brand.site_name', config('app.name', 'Examtube.in'));
            $slides[] = [
                'badge' => 'Welcome',
                'title' => $siteName,
                'description' => $this->cms->setting('brand.tagline', 'Practice smarter. Score higher.'),
                'cta_label' => 'Browse Exams',
                'cta_url' => route('frontend.exams.index'),
                'illustration' => asset('frontend/images/banner.svg'),
                'show_search' => true,
            ];
        }

        return array_slice($slides, 0, 8);
    }

    /**
     * @return array<string, int|string>
     */
    public function stats(?int $orgId = null): array
    {
        $orgId ??= $this->cms->organizationId();
        $cacheKey = 'frontend.stats.'.($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($orgId) {
            $examQuery = Exam::query()->publicCatalog();
            $blogQuery = Blog::query()->published();
            $newsQuery = News::query()->published();
            $questionQuery = Question::query()->publiclyVisible();
            $userQuery = User::query()->where('status', 'active');

            if ($orgId) {
                $examQuery->forOrg($orgId);
                $blogQuery->forOrg($orgId);
                $newsQuery->forOrg($orgId);
                $questionQuery->forOrg($orgId);
            }

            return [
                'exams' => (int) $examQuery->count(),
                'questions' => (int) $questionQuery->count(),
                'blogs' => (int) $blogQuery->count(),
                'news' => (int) $newsQuery->count(),
                'students' => (int) $userQuery->count(),
                'categories' => (int) ExamCategory::query()->when($orgId, fn ($q) => $q->forOrg($orgId))->count(),
            ];
        });
    }

    /**
     * @return Collection<int, Exam>
     */
    public function featuredExams(?int $orgId = null, int $limit = 12): Collection
    {
        return Exam::query()
            ->publicCatalog()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category', 'bannerImage'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Exam>
     */
    public function upcomingExams(?int $orgId = null, int $limit = 4): Collection
    {
        return Exam::query()
            ->publicCatalog()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereNotNull('scheduled_start')
            ->where('scheduled_start', '>', now())
            ->with(['category', 'bannerImage'])
            ->orderBy('scheduled_start')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Question>
     */
    public function randomQuestions(?int $orgId = null, int $limit = 12): Collection
    {
        return Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, ExamCategory>
     */
    public function categories(?int $orgId = null, int $limit = 8): Collection
    {
        return $this->examCategories($orgId, $limit);
    }

    /**
     * @return Collection<int, ExamCategory>
     */
    public function examCategories(?int $orgId = null, int $limit = 20): Collection
    {
        return ExamCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    public function blogCategories(?int $orgId = null, int $limit = 20): Collection
    {
        return BlogCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, NewsCategory>
     */
    public function newsCategories(?int $orgId = null, int $limit = 20): Collection
    {
        return NewsCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, QuestionCategory>
     */
    public function questionCategories(?int $orgId = null, int $limit = 20): Collection
    {
        return QuestionCategory::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('question_categories', 'is_public'),
                fn ($q) => $q->where('is_public', true)
            )
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Blog>
     */
    public function blogs(?int $orgId = null, bool $featured = false, int $limit = 6): Collection
    {
        $query = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category', 'author', 'bannerImage', 'banners']);

        if ($featured) {
            $query->orderByDesc('view_count');
        } else {
            $query->latest('published_at');
        }

        return $query->limit($limit)->get();
    }

    /**
     * @return Collection<int, Blog>
     */
    public function randomBlogsWithBanner(?int $orgId = null, int $limit = 9): Collection
    {
        return Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where(function ($q) {
                $q->whereNotNull('banner_image_id')
                    ->orWhereHas('banners');
            })
            ->with(['category', 'author', 'bannerImage', 'banners'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, News>
     */
    public function news(?int $orgId = null, bool $breaking = false, bool $trending = false, int $limit = 6): Collection
    {
        $query = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category', 'author', 'bannerImage', 'featuredImage', 'banners']);

        if ($breaking) {
            $query->where('is_breaking', true);
        } elseif ($trending) {
            $query->where('is_trending', true);
        } else {
            $query->latest('published_at');
        }

        return $query->limit($limit)->get();
    }

    /**
     * @return Collection<int, News>
     */
    public function randomNewsWithBanner(?int $orgId = null, int $limit = 9): Collection
    {
        return News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where(function ($q) {
                $q->whereNotNull('banner_image_id')
                    ->orWhereHas('banners');
            })
            ->with(['category', 'author', 'bannerImage', 'featuredImage', 'banners'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Testimonial>
     */
    public function testimonials(?int $orgId = null, int $limit = 6): Collection
    {
        return Testimonial::query()
            ->active()
            ->ordered()
            ->with('avatar')
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Faq>
     */
    public function faqs(?int $orgId = null, int $limit = 8): Collection
    {
        return Faq::query()
            ->active()
            ->ordered()
            ->with('category')
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Partner>
     */
    public function partners(?int $orgId = null, int $limit = 12): Collection
    {
        return Partner::query()
            ->active()
            ->ordered()
            ->with('logo')
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->limit($limit)
            ->get();
    }
}
