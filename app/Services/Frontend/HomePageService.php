<?php

namespace App\Services\Frontend;

use App\Models\Blog;
use App\Models\Cms\Faq;
use App\Models\Cms\HeroBanner;
use App\Models\Cms\Partner;
use App\Models\Cms\Testimonial;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

        return [
            'sections' => $sections,
            'banners' => $this->banners($orgId),
            'stats' => $this->stats($orgId),
            'featuredExams' => $this->featuredExams($orgId),
            'upcomingExams' => $this->upcomingExams($orgId),
            'categories' => $this->categories($orgId),
            'featuredBlogs' => $this->blogs($orgId, featured: true),
            'latestBlogs' => $this->blogs($orgId, featured: false),
            'breakingNews' => $this->news($orgId, breaking: true),
            'trendingNews' => $this->news($orgId, trending: true),
            'latestNews' => $this->news($orgId),
            'testimonials' => $this->testimonials($orgId),
            'faqs' => $this->faqs($orgId),
            'partners' => $this->partners($orgId),
            'newsletter' => [
                'title' => $this->cms->setting('newsletter.title', 'Stay exam-ready every week'),
                'subtitle' => $this->cms->setting('newsletter.subtitle', 'Get curated exam alerts, practice tips, and career updates from Examtube.in.'),
                'cta' => $this->cms->setting('newsletter.cta', 'Subscribe'),
            ],
            'cta' => [
                'title' => $this->cms->setting('cta.title', 'Ready to start your next exam?'),
                'subtitle' => $this->cms->setting('cta.subtitle', 'Practice with structured mock tests, track scores, and learn with blogs & news built for exam aspirants.'),
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
     * @return array<string, int|string>
     */
    public function stats(?int $orgId = null): array
    {
        $orgId ??= $this->cms->organizationId();
        $cacheKey = 'frontend.stats.'.($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($orgId) {
            $examQuery = Exam::query()->published();
            $blogQuery = Blog::query()->published();
            $newsQuery = News::query()->published();
            $questionQuery = Question::query();
            $userQuery = User::query();

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
    public function featuredExams(?int $orgId = null, int $limit = 8): Collection
    {
        return Exam::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Exam>
     */
    public function upcomingExams(?int $orgId = null, int $limit = 4): Collection
    {
        return Exam::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereNotNull('scheduled_start')
            ->where('scheduled_start', '>', now())
            ->with(['category'])
            ->orderBy('scheduled_start')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, ExamCategory>
     */
    public function categories(?int $orgId = null, int $limit = 8): Collection
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
     * @return Collection<int, News>
     */
    public function news(?int $orgId = null, bool $breaking = false, bool $trending = false, int $limit = 6): Collection
    {
        $query = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->with(['category', 'author', 'bannerImage', 'featuredImage']);

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
