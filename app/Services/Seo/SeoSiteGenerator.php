<?php

namespace App\Services\Seo;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Cms\SitePage;
use App\Models\Cms\SiteSetting;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsTag;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Frontend\SiteCmsService;
use App\Support\OrganizationRoles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeoSiteGenerator
{
    public const GROUP = 'seo_files';

    public const DEFAULT_CHUNK = 750;

    public function __construct(
        protected SiteCmsService $siteCms,
        protected SitemapImageBuilder $images,
    ) {}

    /**
     * @return array{files: list<string>, generated_at: string, url_counts: array<string, int>}
     */
    public function generate(?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $chunk = max(100, min(50000, (int) $this->setting('chunk_size', self::DEFAULT_CHUNK, $orgId)));
        $baseUrl = rtrim((string) config('app.url'), '/');

        $this->ensureDirectories();

        $urlCounts = [];
        $childSitemaps = [];

        $sections = [
            'pages' => $this->pageUrls($orgId),
            'blogs' => $this->blogUrls($orgId),
            'news' => $this->newsUrls($orgId),
            'exams' => $this->examUrls($orgId),
            'categories' => $this->categoryUrls($orgId),
            'tags' => $this->tagUrls($orgId),
            'authors' => $this->authorUrls($orgId),
        ];

        $questionChunks = $this->questionUrls($orgId)->chunk($chunk);
        foreach ($questionChunks->values() as $index => $chunkUrls) {
            $name = 'questions-'.($index + 1);
            $sections[$name] = $chunkUrls;
        }

        foreach ($sections as $name => $urls) {
            /** @var Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}> $urls */
            if ($urls->isEmpty()) {
                continue;
            }

            $relative = 'sitemaps/'.$name.'.xml';
            $this->writeFile(public_path($relative), $this->renderUrlset($urls));
            $childSitemaps[] = [
                'loc' => $baseUrl.'/'.$relative,
                'lastmod' => now()->toAtomString(),
            ];
            $urlCounts[$name] = $urls->count();
        }

        // Always include core index pages even when content sections are empty
        $static = collect([
            $this->url($baseUrl.'/', now(), 'daily', '1.0'),
            $this->url($baseUrl.'/exams', now(), 'daily', '0.9'),
            $this->url($baseUrl.'/questions', now(), 'daily', '0.9'),
            $this->url($baseUrl.'/questions/categories', now(), 'weekly', '0.6'),
            $this->url($baseUrl.'/blogs', now(), 'daily', '0.8'),
            $this->url($baseUrl.'/news', now(), 'daily', '0.8'),
            $this->url($baseUrl.'/news/trending', now(), 'daily', '0.7'),
            $this->url($baseUrl.'/categories', now(), 'weekly', '0.7'),
            $this->url($baseUrl.'/authors', now(), 'weekly', '0.6'),
        ]);

        $this->writeFile(public_path('sitemaps/static.xml'), $this->renderUrlset($static));
        $childSitemaps[] = [
            'loc' => $baseUrl.'/sitemaps/static.xml',
            'lastmod' => now()->toAtomString(),
        ];
        $urlCounts['static'] = $static->count();

        $imageEntries = collect($sections)
            ->only(['pages', 'blogs', 'news', 'exams', 'categories'])
            ->flatten(1)
            ->filter(fn ($entry) => is_array($entry) && ! empty($entry['images']))
            ->values()
            ->map(fn (array $entry) => [
                'loc' => $entry['loc'],
                'lastmod' => $entry['lastmod'] ?? null,
                'changefreq' => $entry['changefreq'] ?? 'weekly',
                'priority' => $entry['priority'] ?? '0.5',
                'images' => $entry['images'],
            ]);

        $imageCount = $imageEntries->sum(fn (array $entry) => count($entry['images'] ?? []));
        $imageXml = $this->renderUrlset($imageEntries);
        $this->writeFile(public_path('sitemaps/images.xml'), $imageXml);
        $this->writeFile(public_path('image-sitemap.xml'), $imageXml);
        $childSitemaps[] = [
            'loc' => $baseUrl.'/image-sitemap.xml',
            'lastmod' => now()->toAtomString(),
        ];
        $urlCounts['images'] = $imageEntries->count();
        $urlCounts['image_assets'] = $imageCount;

        $this->writeFile(public_path('sitemap.xml'), $this->renderSitemapIndex($childSitemaps));
        $this->writeFile(public_path('robots.txt'), $this->renderRobots($baseUrl, $orgId));
        $this->writeFile(public_path('humans.txt'), $this->renderHumans($orgId));
        $this->writeFile(public_path('.well-known/security.txt'), $this->renderSecurityTxt($orgId));
        $this->writeFile(public_path('feeds/rss.xml'), $this->renderRss($orgId, $baseUrl));
        $this->writeFile(public_path('feeds/atom.xml'), $this->renderAtom($orgId, $baseUrl));
        $this->writeFile(public_path('manifest.json'), $this->renderManifest($orgId, $baseUrl));

        $generatedAt = now()->toIso8601String();
        $this->persistMeta([
            'last_generated_at' => $generatedAt,
            'url_counts' => $urlCounts,
            'sitemap_files' => array_column($childSitemaps, 'loc'),
        ], $orgId);

        $this->siteCms->clearCache($orgId);

        return [
            'files' => array_merge(
                ['sitemap.xml', 'image-sitemap.xml', 'robots.txt', 'humans.txt', '.well-known/security.txt', 'feeds/rss.xml', 'feeds/atom.xml', 'manifest.json'],
                array_map(fn ($s) => str_replace($baseUrl.'/', '', $s['loc']), $childSitemaps)
            ),
            'generated_at' => $generatedAt,
            'url_counts' => $urlCounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function status(?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $meta = $this->setting('generation_meta', [], $orgId);
        if (! is_array($meta)) {
            $meta = [];
        }

        return [
            'last_generated_at' => $meta['last_generated_at'] ?? null,
            'url_counts' => $meta['url_counts'] ?? [],
            'sitemap_files' => $meta['sitemap_files'] ?? [],
            'chunk_size' => (int) $this->setting('chunk_size', self::DEFAULT_CHUNK, $orgId),
            'files_exist' => [
                'sitemap' => is_file(public_path('sitemap.xml')),
                'image_sitemap' => is_file(public_path('image-sitemap.xml')),
                'robots' => is_file(public_path('robots.txt')),
                'rss' => is_file(public_path('feeds/rss.xml')),
                'atom' => is_file(public_path('feeds/atom.xml')),
                'humans' => is_file(public_path('humans.txt')),
                'security' => is_file(public_path('.well-known/security.txt')),
                'manifest' => is_file(public_path('manifest.json')),
            ],
            'public_urls' => [
                'sitemap' => url('/sitemap.xml'),
                'image_sitemap' => url('/image-sitemap.xml'),
                'robots' => url('/robots.txt'),
                'rss' => url('/feeds/rss.xml'),
                'atom' => url('/feeds/atom.xml'),
                'humans' => url('/humans.txt'),
                'security' => url('/.well-known/security.txt'),
                'manifest' => url('/manifest.json'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data, ?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        $map = [
            'chunk_size' => ['type' => 'integer', 'label' => 'Sitemap chunk size'],
            'robots_extra' => ['type' => 'text', 'label' => 'Extra robots.txt rules'],
            'humans_text' => ['type' => 'text', 'label' => 'humans.txt content'],
            'security_contact_email' => ['type' => 'string', 'label' => 'security.txt contact email'],
            'security_policy_url' => ['type' => 'string', 'label' => 'security.txt policy URL'],
            'manifest_name' => ['type' => 'string', 'label' => 'Manifest name'],
            'manifest_short_name' => ['type' => 'string', 'label' => 'Manifest short name'],
            'manifest_theme_color' => ['type' => 'string', 'label' => 'Manifest theme color'],
            'manifest_background_color' => ['type' => 'string', 'label' => 'Manifest background color'],
        ];

        foreach ($map as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            SiteSetting::query()->updateOrCreate(
                ['organization_id' => $orgId, 'group' => self::GROUP, 'key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) ($value ?? ''),
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }

        $this->siteCms->clearCache($orgId);

        return $this->status($orgId);
    }

    public function seedDefaults(?int $orgId): void
    {
        $defaults = [
            'chunk_size' => [(string) self::DEFAULT_CHUNK, 'integer', 'Sitemap chunk size'],
            'robots_extra' => ['', 'text', 'Extra robots.txt rules'],
            'humans_text' => [
                "/* TEAM */\nOwner: Examtube Learning Technologies\nSite: https://examtube.in\nLocation: Mumbai, India\n\n/* THANKS */\nLaravel, Tailwind CSS\n\n/* SITE */\nLast update: ".now()->toDateString()."\nLanguage: en-IN\nStandards: HTML5, CSS3",
                'text',
                'humans.txt content',
            ],
            'security_contact_email' => ['support@examtube.in', 'string', 'security.txt contact email'],
            'security_policy_url' => ['', 'string', 'security.txt policy URL'],
            'manifest_name' => ['Examtube.in', 'string', 'Manifest name'],
            'manifest_short_name' => ['Examtube', 'string', 'Manifest short name'],
            'manifest_theme_color' => ['#0f766e', 'string', 'Manifest theme color'],
            'manifest_background_color' => ['#0b1220', 'string', 'Manifest background color'],
        ];

        foreach ($defaults as $key => [$value, $type, $label]) {
            SiteSetting::query()->updateOrCreate(
                ['organization_id' => $orgId, 'group' => self::GROUP, 'key' => $key],
                ['value' => $value, 'type' => $type, 'label' => $label]
            );
        }
    }

    protected function ensureDirectories(): void
    {
        File::ensureDirectoryExists(public_path('sitemaps'));
        File::ensureDirectoryExists(public_path('feeds'));
        File::ensureDirectoryExists(public_path('.well-known'));
    }

    protected function writeFile(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    /**
     * @param  Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}>  $urls
     */
    protected function renderUrlset(Collection $urls): string
    {
        $hasImages = $urls->contains(fn (array $url) => ! empty($url['images']));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= $hasImages
            ? '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n"
            : '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.$this->escape($url['loc'])."</loc>\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$this->escape($url['lastmod'])."</lastmod>\n";
            }
            if (! empty($url['changefreq'])) {
                $xml .= '    <changefreq>'.$this->escape($url['changefreq'])."</changefreq>\n";
            }
            if (! empty($url['priority'])) {
                $xml .= '    <priority>'.$this->escape($url['priority'])."</priority>\n";
            }
            foreach ($url['images'] ?? [] as $image) {
                if (empty($image['loc'])) {
                    continue;
                }
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>'.$this->escape((string) $image['loc'])."</image:loc>\n";
                if (! empty($image['title'])) {
                    $xml .= '      <image:title>'.$this->escape((string) $image['title'])."</image:title>\n";
                }
                if (! empty($image['caption'])) {
                    $xml .= '      <image:caption>'.$this->escape((string) $image['caption'])."</image:caption>\n";
                }
                $xml .= "    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * @param  list<array{loc: string, lastmod: string}>  $sitemaps
     */
    protected function renderSitemapIndex(array $sitemaps): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($sitemaps as $sitemap) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>'.$this->escape($sitemap['loc'])."</loc>\n";
            $xml .= '    <lastmod>'.$this->escape($sitemap['lastmod'])."</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        return $xml;
    }

    protected function renderRobots(string $baseUrl, ?int $orgId): string
    {
        $extra = trim((string) $this->setting('robots_extra', '', $orgId));
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /account',
            'Disallow: /account/',
            'Disallow: /attempts',
            'Disallow: /attempts/',
            'Disallow: /sanctum/',
            'Disallow: /up',
            '',
            'Sitemap: '.$baseUrl.'/sitemap.xml',
            'Sitemap: '.$baseUrl.'/image-sitemap.xml',
        ];

        if ($extra !== '') {
            $lines[] = '';
            $lines[] = '# Custom rules';
            $lines[] = $extra;
        }

        return implode("\n", $lines)."\n";
    }

    protected function renderHumans(?int $orgId): string
    {
        $text = (string) $this->setting('humans_text', '', $orgId);

        return $text !== '' ? rtrim($text)."\n" : "/* TEAM */\nSite: Examtube.in\n";
    }

    protected function renderSecurityTxt(?int $orgId): string
    {
        $email = (string) $this->setting('security_contact_email', 'support@examtube.in', $orgId);
        $policy = trim((string) $this->setting('security_policy_url', '', $orgId));
        $lines = [
            'Contact: mailto:'.$email,
            'Preferred-Languages: en, hi',
            'Expires: '.now()->addYear()->toIso8601String(),
        ];
        if ($policy !== '') {
            $lines[] = 'Policy: '.$policy;
        }

        return implode("\n", $lines)."\n";
    }

    protected function renderManifest(?int $orgId, string $baseUrl): string
    {
        $name = (string) $this->setting('manifest_name', site_setting('brand.site_name', 'Examtube.in'), $orgId);
        $short = (string) $this->setting('manifest_short_name', 'Examtube', $orgId);
        $theme = (string) $this->setting('manifest_theme_color', '#0f766e', $orgId);
        $bg = (string) $this->setting('manifest_background_color', '#0b1220', $orgId);

        return json_encode([
            'name' => $name,
            'short_name' => $short,
            'description' => (string) site_setting('seo.default_description', 'Online exams and learning hub'),
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => $bg,
            'theme_color' => $theme,
            'lang' => 'en-IN',
            'dir' => 'ltr',
            'scope' => '/',
            'icons' => [
                [
                    'src' => $baseUrl.'/favicon.ico',
                    'sizes' => '48x48',
                    'type' => 'image/x-icon',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    }

    protected function renderRss(?int $orgId, string $baseUrl): string
    {
        $siteName = e((string) site_setting('brand.site_name', 'Examtube.in'));
        $description = e((string) site_setting('seo.default_description', 'Latest updates'));
        $items = $this->feedItems($orgId, 50);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0">'."\n<channel>\n";
        $xml .= '<title>'.$siteName."</title>\n";
        $xml .= '<link>'.$this->escape($baseUrl)."</link>\n";
        $xml .= '<description>'.$description."</description>\n";
        $xml .= '<language>en-in</language>\n';
        $xml .= '<lastBuildDate>'.e(now()->toRfc2822String())."</lastBuildDate>\n";

        foreach ($items as $item) {
            $xml .= "<item>\n";
            $xml .= '<title>'.e($item['title'])."</title>\n";
            $xml .= '<link>'.$this->escape($item['link'])."</link>\n";
            $xml .= '<guid isPermaLink="true">'.$this->escape($item['link'])."</guid>\n";
            $xml .= '<pubDate>'.e($item['pubDate'])."</pubDate>\n";
            $xml .= '<description>'.e($item['description'])."</description>\n";
            $xml .= '<category>'.e($item['category'])."</category>\n";
            $xml .= "</item>\n";
        }

        $xml .= "</channel>\n</rss>\n";

        return $xml;
    }

    protected function renderAtom(?int $orgId, string $baseUrl): string
    {
        $siteName = e((string) site_setting('brand.site_name', 'Examtube.in'));
        $items = $this->feedItems($orgId, 50);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">'."\n";
        $xml .= '<title>'.$siteName."</title>\n";
        $xml .= '<link href="'.$this->escape($baseUrl).'" rel="alternate"/>'."\n";
        $xml .= '<link href="'.$this->escape($baseUrl.'/feeds/atom.xml').'" rel="self"/>'."\n";
        $xml .= '<id>'.$this->escape($baseUrl.'/')."</id>\n";
        $xml .= '<updated>'.e(now()->toAtomString())."</updated>\n";

        foreach ($items as $item) {
            $xml .= "<entry>\n";
            $xml .= '<title>'.e($item['title'])."</title>\n";
            $xml .= '<link href="'.$this->escape($item['link']).'"/>'."\n";
            $xml .= '<id>'.$this->escape($item['link'])."</id>\n";
            $xml .= '<updated>'.e($item['updated'])."</updated>\n";
            $xml .= '<summary>'.e($item['description'])."</summary>\n";
            $xml .= "</entry>\n";
        }

        $xml .= "</feed>\n";

        return $xml;
    }

    /**
     * @return list<array{title: string, link: string, description: string, pubDate: string, updated: string, category: string}>
     */
    protected function feedItems(?int $orgId, int $limit): array
    {
        $blogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['title', 'slug', 'excerpt', 'published_at', 'updated_at']);

        $news = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['title', 'slug', 'excerpt', 'published_at', 'updated_at']);

        $merged = collect();
        foreach ($blogs as $blog) {
            $date = $blog->published_at ?? $blog->updated_at ?? now();
            $merged->push([
                'title' => (string) $blog->title,
                'link' => route('frontend.blogs.show', $blog->slug),
                'description' => Str::limit(strip_tags((string) ($blog->excerpt ?? '')), 280),
                'pubDate' => Carbon::parse($date)->toRfc2822String(),
                'updated' => Carbon::parse($blog->updated_at ?? $date)->toAtomString(),
                'category' => 'Blog',
                'sort' => Carbon::parse($date)->timestamp,
            ]);
        }
        foreach ($news as $row) {
            $date = $row->published_at ?? $row->updated_at ?? now();
            $merged->push([
                'title' => (string) $row->title,
                'link' => route('frontend.news.show', $row->slug),
                'description' => Str::limit(strip_tags((string) ($row->excerpt ?? '')), 280),
                'pubDate' => Carbon::parse($date)->toRfc2822String(),
                'updated' => Carbon::parse($row->updated_at ?? $date)->toAtomString(),
                'category' => 'News',
                'sort' => Carbon::parse($date)->timestamp,
            ]);
        }

        return $merged->sortByDesc('sort')->take($limit)->values()->map(function ($item) {
            unset($item['sort']);

            return $item;
        })->all();
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}>
     */
    protected function pageUrls(?int $orgId): Collection
    {
        return SitePage::query()
            ->published()
            ->with(['bannerImage'])
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->get(['id', 'slug', 'title', 'excerpt', 'seo_title', 'seo_description', 'banner_image_id', 'updated_at'])
            ->map(fn ($page) => $this->url(
                url('/'.$page->slug),
                $page->updated_at,
                'monthly',
                '0.6',
                $this->images->forPage($page)
            ));
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}>
     */
    protected function blogUrls(?int $orgId): Collection
    {
        return Blog::query()
            ->published()
            ->with(['ogImage', 'bannerImage', 'banners', 'galleryAttachments'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->get([
                'id', 'slug', 'title', 'excerpt', 'seo_title', 'og_title',
                'banner_image_id', 'og_image_id', 'updated_at', 'published_at',
            ])
            ->map(fn ($blog) => $this->url(
                route('frontend.blogs.show', $blog->slug),
                $blog->updated_at ?? $blog->published_at,
                'weekly',
                '0.7',
                $this->images->forBlog($blog)
            ));
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}>
     */
    protected function newsUrls(?int $orgId): Collection
    {
        return News::query()
            ->published()
            ->with(['ogImage', 'featuredImage', 'bannerImage', 'banners', 'galleryAttachments'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->get([
                'id', 'slug', 'title', 'excerpt', 'seo_title', 'og_title',
                'banner_image_id', 'featured_image_id', 'og_image_id', 'updated_at', 'published_at',
            ])
            ->map(fn ($news) => $this->url(
                route('frontend.news.show', $news->slug),
                $news->updated_at ?? $news->published_at,
                'daily',
                '0.7',
                $this->images->forNews($news)
            ));
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}>
     */
    protected function examUrls(?int $orgId): Collection
    {
        return Exam::query()
            ->publicCatalog()
            ->with(['ogImage', 'bannerImage'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereNotNull('slug')
            ->get([
                'id', 'slug', 'title', 'description', 'meta_title', 'meta_description', 'og_title',
                'banner_image_id', 'og_image_id', 'updated_at',
            ])
            ->map(fn ($exam) => $this->url(
                route('frontend.exams.show', $exam->slug),
                $exam->updated_at,
                'weekly',
                '0.8',
                $this->images->forExam($exam)
            ));
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    protected function questionUrls(?int $orgId): Collection
    {
        return Question::query()
            ->publiclyVisible()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->get(['slug', 'updated_at'])
            ->map(fn ($question) => $this->url(
                route('frontend.questions.show', $question->slug),
                $question->updated_at,
                'weekly',
                '0.6'
            ));
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}>
     */
    protected function categoryUrls(?int $orgId): Collection
    {
        $urls = collect();

        $examCats = ExamCategory::query()
            ->with(['ogImage'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->get(['id', 'slug', 'name', 'description', 'meta_title', 'meta_description', 'og_image_id', 'updated_at']);
        foreach ($examCats as $cat) {
            $urls->push($this->url(
                route('frontend.categories.show', $cat->slug),
                $cat->updated_at,
                'weekly',
                '0.6',
                $this->images->forCategory($cat)
            ));
        }

        $blogCats = BlogCategory::query()
            ->with(['ogImage'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->get(['id', 'slug', 'name', 'description', 'meta_title', 'meta_description', 'og_image_id', 'updated_at']);
        foreach ($blogCats as $cat) {
            $urls->push($this->url(
                route('frontend.blogs.category', $cat->slug),
                $cat->updated_at,
                'weekly',
                '0.5',
                $this->images->forCategory($cat)
            ));
        }

        $newsCats = NewsCategory::query()
            ->with(['ogImage'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->get(['id', 'slug', 'name', 'description', 'meta_title', 'meta_description', 'og_image_id', 'updated_at']);
        foreach ($newsCats as $cat) {
            $urls->push($this->url(
                route('frontend.news.category', $cat->slug),
                $cat->updated_at,
                'weekly',
                '0.5',
                $this->images->forCategory($cat)
            ));
        }

        $questionCats = QuestionCategory::query()
            ->with(['ogImage'])
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->publiclyVisible()
            ->whereNotNull('slug')
            ->get(['id', 'slug', 'name', 'description', 'meta_title', 'meta_description', 'og_image_id', 'updated_at']);
        foreach ($questionCats as $cat) {
            $urls->push($this->url(
                route('frontend.questions.category', $cat->slug),
                $cat->updated_at,
                'weekly',
                '0.5',
                $this->images->forCategory($cat)
            ));
        }

        return $urls;
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    protected function tagUrls(?int $orgId): Collection
    {
        $urls = collect();

        $blogTags = BlogTag::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereNotNull('slug')
            ->whereHas('blogs', fn ($q) => $q->published()->when($orgId, fn ($inner) => $inner->forOrg($orgId)))
            ->get(['slug', 'updated_at']);

        foreach ($blogTags as $tag) {
            $urls->push($this->url(route('frontend.blogs.tag', $tag->slug), $tag->updated_at, 'weekly', '0.4'));
        }

        $newsTags = NewsTag::query()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->whereNotNull('slug')
            ->whereHas('news', fn ($q) => $q->published()->when($orgId, fn ($inner) => $inner->forOrg($orgId)))
            ->get(['slug', 'updated_at']);

        foreach ($newsTags as $tag) {
            $urls->push($this->url(route('frontend.news.tag', $tag->slug), $tag->updated_at, 'weekly', '0.4'));
        }

        return $urls;
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string, changefreq: string, priority: string}>
     */
    protected function authorUrls(?int $orgId): Collection
    {
        $authorIds = UserOrganization::query()
            ->whereIn('role', [OrganizationRoles::ADMIN, OrganizationRoles::ORG_ADMIN])
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->pluck('user_id')
            ->unique()
            ->all();

        if ($authorIds === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $authorIds)
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at'])
            ->map(fn ($user) => $this->url(
                route('frontend.authors.show', $user->slug),
                $user->updated_at,
                'weekly',
                '0.5'
            ));
    }

    /**
     * @param  list<array{loc: string, title: ?string, caption: ?string}>  $images
     * @return array{loc: string, lastmod: ?string, changefreq: string, priority: string, images?: list<array{loc: string, title: ?string, caption: ?string}>}
     */
    protected function url(string $loc, mixed $lastmod, string $changefreq, string $priority, array $images = []): array
    {
        $formatted = null;
        if ($lastmod) {
            try {
                $formatted = Carbon::parse($lastmod)->toAtomString();
            } catch (\Throwable) {
                $formatted = null;
            }
        }

        $entry = [
            'loc' => $loc,
            'lastmod' => $formatted,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];

        if ($images !== []) {
            $entry['images'] = $images;
        }

        return $entry;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function setting(string $key, mixed $default = null, ?int $orgId = null): mixed
    {
        return $this->siteCms->setting(self::GROUP.'.'.$key, $default, $orgId);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function persistMeta(array $meta, ?int $orgId): void
    {
        SiteSetting::query()->updateOrCreate(
            ['organization_id' => $orgId, 'group' => self::GROUP, 'key' => 'generation_meta'],
            [
                'value' => json_encode($meta),
                'type' => 'json',
                'label' => 'SEO generation metadata',
            ]
        );
    }
}
