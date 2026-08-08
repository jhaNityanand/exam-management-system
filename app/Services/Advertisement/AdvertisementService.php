<?php

namespace App\Services\Advertisement;

use App\Models\Cms\AdPlacement;
use App\Models\Cms\Advertisement;
use App\Models\Cms\GoogleAdvertisement;
use App\Models\Cms\SiteSetting;
use App\Services\Frontend\SiteCmsService;
use App\Support\AdvertisementCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvertisementService
{
    public const SETTINGS_GROUP = 'advertisements';

    public function __construct(
        protected SiteCmsService $siteCms,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Advertisement>
     */
    public function listAdvertisements(int $orgId, array $filters = []): Collection
    {
        return Advertisement::query()
            ->forOrg($orgId)
            ->with('image')
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAdvertisement(array $data, int $orgId): Advertisement
    {
        $payload = $this->normalizeAdvertisementPayload($data);
        $payload['organization_id'] = $orgId;
        $payload['sort_order'] = $payload['sort_order']
            ?? ((int) Advertisement::query()->forOrg($orgId)->max('sort_order') + 1);

        $ad = Advertisement::query()->create($payload);
        $this->forgetCache($orgId);

        return $ad->load('image');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAdvertisement(Advertisement $ad, array $data): Advertisement
    {
        $ad->update($this->normalizeAdvertisementPayload($data));
        $this->forgetCache((int) $ad->organization_id);

        return $ad->fresh('image');
    }

    public function deleteAdvertisement(Advertisement $ad): void
    {
        $orgId = (int) $ad->organization_id;

        DB::transaction(function () use ($ad) {
            AdPlacement::query()
                ->where('advertisement_id', $ad->id)
                ->delete();
            $ad->delete();
        });

        $this->forgetCache($orgId);
    }

    /**
     * @return Collection<int, GoogleAdvertisement>
     */
    public function listGoogleAds(int $orgId, array $filters = []): Collection
    {
        return GoogleAdvertisement::query()
            ->forOrg($orgId)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createGoogleAd(array $data, int $orgId): GoogleAdvertisement
    {
        $data['organization_id'] = $orgId;
        $data['sort_order'] = $data['sort_order']
            ?? ((int) GoogleAdvertisement::query()->forOrg($orgId)->max('sort_order') + 1);

        $ad = GoogleAdvertisement::query()->create($data);
        $this->forgetCache($orgId);

        return $ad;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGoogleAd(GoogleAdvertisement $ad, array $data): GoogleAdvertisement
    {
        $ad->update($data);
        $this->forgetCache((int) $ad->organization_id);

        return $ad->fresh();
    }

    public function deleteGoogleAd(GoogleAdvertisement $ad): void
    {
        $orgId = (int) $ad->organization_id;

        DB::transaction(function () use ($ad) {
            AdPlacement::query()
                ->where('google_advertisement_id', $ad->id)
                ->delete();
            $ad->delete();
        });

        $this->forgetCache($orgId);
    }

    /**
     * @return Collection<int, AdPlacement>
     */
    public function placementsForPage(int $orgId, string $pageKey): Collection
    {
        return AdPlacement::query()
            ->forOrg($orgId)
            ->forPage($pageKey)
            ->with(['advertisement.image', 'googleAdvertisement'])
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPlacement(array $data, int $orgId): AdPlacement
    {
        $this->assertPlacementPayload($data, $orgId);

        $pageKey = $data['page_key'];
        $positionKey = $data['position_key'];

        if (! AdvertisementCatalog::allowsMultiple($positionKey)) {
            $exists = AdPlacement::query()
                ->forOrg($orgId)
                ->where('page_key', $pageKey)
                ->where('position_key', $positionKey)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'position_key' => 'This position already has an advertisement. Replace or remove it first.',
                ]);
            }
        }

        $sortOrder = $data['sort_order']
            ?? ((int) AdPlacement::query()
                ->forOrg($orgId)
                ->where('page_key', $pageKey)
                ->where('position_key', $positionKey)
                ->max('sort_order') + 1);

        $placement = AdPlacement::query()->create([
            'organization_id' => $orgId,
            'page_key' => $pageKey,
            'position_key' => $positionKey,
            'source_type' => $data['source_type'],
            'advertisement_id' => $data['source_type'] === AdvertisementCatalog::SOURCE_CUSTOM
                ? ($data['advertisement_id'] ?? null)
                : null,
            'google_advertisement_id' => $data['source_type'] === AdvertisementCatalog::SOURCE_GOOGLE
                ? ($data['google_advertisement_id'] ?? null)
                : null,
            'sort_order' => $sortOrder,
            'is_enabled' => array_key_exists('is_enabled', $data) ? (bool) $data['is_enabled'] : true,
        ]);

        $this->forgetCache($orgId);

        return $placement->load(['advertisement.image', 'googleAdvertisement']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePlacement(AdPlacement $placement, array $data): AdPlacement
    {
        $orgId = (int) $placement->organization_id;

        if (isset($data['source_type']) || isset($data['advertisement_id']) || isset($data['google_advertisement_id'])) {
            $merged = [
                'source_type' => $data['source_type'] ?? $placement->source_type,
                'advertisement_id' => $data['advertisement_id'] ?? $placement->advertisement_id,
                'google_advertisement_id' => $data['google_advertisement_id'] ?? $placement->google_advertisement_id,
                'page_key' => $placement->page_key,
                'position_key' => $placement->position_key,
            ];
            $this->assertPlacementPayload($merged, $orgId);

            $placement->source_type = $merged['source_type'];
            $placement->advertisement_id = $merged['source_type'] === AdvertisementCatalog::SOURCE_CUSTOM
                ? $merged['advertisement_id']
                : null;
            $placement->google_advertisement_id = $merged['source_type'] === AdvertisementCatalog::SOURCE_GOOGLE
                ? $merged['google_advertisement_id']
                : null;
        }

        if (array_key_exists('is_enabled', $data)) {
            $placement->is_enabled = (bool) $data['is_enabled'];
        }
        if (array_key_exists('sort_order', $data)) {
            $placement->sort_order = (int) $data['sort_order'];
        }

        $placement->save();
        $this->forgetCache($orgId);

        return $placement->fresh(['advertisement.image', 'googleAdvertisement']);
    }

    public function deletePlacement(AdPlacement $placement): void
    {
        $orgId = (int) $placement->organization_id;
        $placement->delete();
        $this->forgetCache($orgId);
    }

    /**
     * @return array{header_code: string, footer_code: string}
     */
    public function customCode(int $orgId): array
    {
        return [
            'header_code' => (string) $this->siteCms->setting(self::SETTINGS_GROUP.'.header_code', '', $orgId),
            'footer_code' => (string) $this->siteCms->setting(self::SETTINGS_GROUP.'.footer_code', '', $orgId),
        ];
    }

    /**
     * @param  array{header_code?: string, footer_code?: string}  $data
     * @return array{header_code: string, footer_code: string}
     */
    public function updateCustomCode(array $data, int $orgId): array
    {
        $map = [
            'header_code' => ['type' => 'text', 'label' => 'Advertisement header code'],
            'footer_code' => ['type' => 'text', 'label' => 'Advertisement footer code'],
        ];

        foreach ($map as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            SiteSetting::query()->updateOrCreate(
                ['organization_id' => $orgId, 'group' => self::SETTINGS_GROUP, 'key' => $key],
                [
                    'value' => (string) ($data[$key] ?? ''),
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }

        $this->siteCms->clearCache($orgId);
        $this->forgetCache($orgId);

        return $this->customCode($orgId);
    }

    public function seedDefaults(int $orgId, bool $forcePlacements = false): void
    {
        $this->seedGoogleAdUnits($orgId);
        $this->seedGlobalCustomCode($orgId);
        $this->seedDefaultPlacements($orgId, $forcePlacements);

        $this->forgetCache($orgId);
    }

    /**
     * Upsert the canonical Google AdSense units (exact publisher snippets).
     */
    public function seedGoogleAdUnits(int $orgId): void
    {
        $client = 'ca-pub-3495821309562824';

        $units = [
            [
                'name' => 'Display Ad (Horizontal)',
                'ad_slot' => '8279166266',
                'ad_format' => 'horizontal',
                'notes' => 'Display ad Horizontal — default for every main-content section placement.',
                'code' => <<<'HTML'
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3495821309562824"
crossorigin="anonymous"></script>

<!-- Display ad Horizontal -->
<ins class="adsbygoogle"
style="display:block"
data-ad-client="ca-pub-3495821309562824"
data-ad-slot="8279166266"
data-ad-format="auto"
data-full-width-responsive="true"></ins>

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
HTML,
            ],
            [
                'name' => 'Display Ad (Vertical)',
                'ad_slot' => '9013663436',
                'ad_format' => 'vertical',
                'notes' => 'Display ad Vertical — default for every left/right sidebar section placement.',
                'code' => <<<'HTML'
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3495821309562824"
crossorigin="anonymous"></script>

<!-- Display ad Vertical -->
<ins class="adsbygoogle"
style="display:block"
data-ad-client="ca-pub-3495821309562824"
data-ad-slot="9013663436"
data-ad-format="auto"
data-full-width-responsive="true"></ins>

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
HTML,
            ],
            [
                'name' => 'In-Article Ad',
                'ad_slot' => '5461431234',
                'ad_format' => 'in-article',
                'notes' => 'In-article Ad — between content sections and listing card groups.',
                'code' => <<<'HTML'
<script async src="https://pagead2.googlesyndication.com/pagead/js?client=ca-pub-3495821309562824"
crossorigin="anonymous"></script>

<ins class="adsbygoogle"
style="display:block; text-align:center;"
data-ad-layout="in-article"
data-ad-format="fluid"
data-ad-client="ca-pub-3495821309562824"
data-ad-slot="5461431234"></ins>

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
HTML,
            ],
        ];

        foreach ($units as $index => $unit) {
            GoogleAdvertisement::query()->updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'ad_slot' => $unit['ad_slot'],
                ],
                [
                    'name' => $unit['name'],
                    'code' => trim($unit['code'])."\n",
                    'ad_client' => $client,
                    'ad_format' => $unit['ad_format'],
                    'notes' => $unit['notes'],
                    'sort_order' => $index + 1,
                    'status' => 'active',
                ]
            );
        }
    }

    /**
     * Seed exact global header/footer custom code. Footer stays empty.
     */
    public function seedGlobalCustomCode(int $orgId): void
    {
        $header = <<<'HTML'
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-35TPDL6YPR"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());

gtag('config', 'G-35TPDL6YPR');
</script>

<meta name="p:domain_verify" content="a970034f682bb9bbc89a7eb02ee49cfe"/>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3495821309562824"
crossorigin="anonymous"></script>
HTML;

        $this->updateCustomCode([
            'header_code' => trim($header)."\n",
            'footer_code' => '',
        ], $orgId);
    }

    /**
     * Create default Google-only placements for core public pages.
     */
    public function seedDefaultPlacements(int $orgId, bool $replaceExisting = false): void
    {
        // Retire placements that are no longer represented in the preview.
        // Right-sidebar ads now belong directly after a named sidebar section.
        // Hero inner header ads (above_title / below_title) are retired in favor of after_header.
        AdPlacement::query()
            ->forOrg($orgId)
            ->whereIn('position_key', ['right_top', 'left_sidebar', 'right_sidebar', 'above_title', 'below_title', 'right_after_overview', 'right_after_webcam', 'right_after_palette'])
            ->delete();

        $vertical = GoogleAdvertisement::query()
            ->forOrg($orgId)
            ->where('ad_slot', '9013663436')
            ->first();
        $horizontal = GoogleAdvertisement::query()
            ->forOrg($orgId)
            ->where('ad_slot', '8279166266')
            ->first();

        if (! $vertical || ! $horizontal) {
            return;
        }

        // Seed placements for every catalog page (attempt, result, rules, FAQs, account, CMS, errors, etc.).
        $pageKeys = AdvertisementCatalog::pageKeys();

        if (! $replaceExisting) {
            foreach ($pageKeys as $pageKey) {
                $validPositions = AdvertisementCatalog::positionKeysForPage($pageKey);
                $stale = AdPlacement::query()
                    ->withTrashed()
                    ->forOrg($orgId)
                    ->where('page_key', $pageKey);

                if ($validPositions === []) {
                    $stale->forceDelete();
                } else {
                    $stale->whereNotIn('position_key', $validPositions)->forceDelete();
                }
            }
        }

        if ($replaceExisting) {
            AdPlacement::query()
                ->withTrashed()
                ->forOrg($orgId)
                ->whereIn('page_key', $pageKeys)
                ->forceDelete();
        }

        $rows = [];

        foreach ($pageKeys as $pageKey) {
            // Do not seed any ads for the home/landing page
            if ($pageKey === 'home') {
                continue;
            }

            $page = AdvertisementCatalog::page($pageKey);
            if (! $page) {
                continue;
            }

            $positions = $page['positions'] ?? [];

            // Sidebar sections: maximum 1 vertical Google unit after the primary sidebar section.
            $sidebarSlots = array_values(array_filter(
                $positions,
                fn (string $key) => AdvertisementCatalog::isSidePlacementSlot($key)
            ));
            if (! empty($sidebarSlots)) {
                $rows[] = $this->placementRow($orgId, $pageKey, $sidebarSlots[0], $vertical->id, 1);
            }

            // Main-content section slots: pick 1 primary content slot (excluding after_header & above_title).
            $contentPositions = array_values(array_filter(
                $positions,
                fn (string $key) => ! AdvertisementCatalog::isSidePlacementSlot($key)
                    && $key !== 'after_header'
                    && $key !== 'above_title'
            ));

            if (! empty($contentPositions)) {
                // Select 1 primary non-intrusive content position (e.g., after_content, below_items, or between_sections)
                $chosenPosition = in_array('after_content', $contentPositions, true)
                    ? 'after_content'
                    : (in_array('below_items', $contentPositions, true)
                        ? 'below_items'
                        : $contentPositions[0]);

                $rows[] = $this->placementRow($orgId, $pageKey, $chosenPosition, $horizontal->id, 1);
            }
        }

        foreach ($rows as $row) {
            $slotTaken = AdPlacement::query()
                ->withTrashed()
                ->forOrg($orgId)
                ->where('page_key', $row['page_key'])
                ->where('position_key', $row['position_key'])
                ->where('sort_order', $row['sort_order'])
                ->exists();

            if ($slotTaken) {
                continue;
            }

            // Multi-slot: allow stacking; single-slot: skip if any placement already exists
            if (! AdvertisementCatalog::allowsMultiple($row['position_key'])) {
                $taken = AdPlacement::query()
                    ->withTrashed()
                    ->forOrg($orgId)
                    ->where('page_key', $row['page_key'])
                    ->where('position_key', $row['position_key'])
                    ->exists();
                if ($taken) {
                    continue;
                }
            }

            AdPlacement::query()->create($row);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function placementRow(int $orgId, string $pageKey, string $positionKey, int $googleId, int $sortOrder): array
    {
        return [
            'organization_id' => $orgId,
            'page_key' => $pageKey,
            'position_key' => $positionKey,
            'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
            'advertisement_id' => null,
            'google_advertisement_id' => $googleId,
            'sort_order' => $sortOrder,
            'is_enabled' => true,
        ];
    }

    /**
     * @return Collection<int, AdPlacement>
     */
    public function forPlacement(string $pageKey, string $positionKey, ?int $orgId = null): Collection
    {
        $orgId ??= current_organization_id();
        $cacheKey = 'frontend.ads.v2.'.$pageKey.'.'.$positionKey.'.'.($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($pageKey, $positionKey, $orgId) {
            return AdPlacement::query()
                ->enabled()
                ->where('page_key', $pageKey)
                ->where('position_key', $positionKey)
                ->with(['advertisement.image', 'googleAdvertisement'])
                ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                    $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
                }))
                ->ordered()
                ->get()
                ->filter(function (AdPlacement $placement) {
                    if ($placement->isGoogle()) {
                        $google = $placement->googleAdvertisement;

                        return $google && $google->status === 'active' && filled($google->code);
                    }

                    $ad = $placement->advertisement;

                    return $ad && $ad->status === 'active';
                })
                ->values();
        });
    }

    /**
     * Render advertisements for a catalog page/position (or legacy single-key slot).
     */
    public function renderSlot(string $pageOrLegacy, ?string $positionKey = null, ?int $orgId = null): string
    {
        [$pageKey, $position] = $this->resolveSlotKeys($pageOrLegacy, $positionKey);
        if ($pageKey === '' || $position === '' || $pageKey === 'home' || $position === 'after_header') {
            return '';
        }

        $placements = $this->forPlacement($pageKey, $position, $orgId);
        if ($placements->isEmpty()) {
            return '';
        }

        $variant = match (true) {
            AdvertisementCatalog::isSidePlacementSlot($position) => 'rail',
            $position === 'above_footer' => 'footer',
            default => 'inline',
        };

        $renderedSlots = [];
        foreach ($placements as $placement) {
            $html = $this->renderPlacementUnit($placement);
            if ($html === '') {
                continue;
            }

            $source = $placement->isGoogle()
                ? AdvertisementCatalog::SOURCE_GOOGLE
                : AdvertisementCatalog::SOURCE_CUSTOM;
            $previewLabel = $source === AdvertisementCatalog::SOURCE_GOOGLE ? 'Google Ad' : 'Custom Ad';

            $renderedSlots[] = view('frontend.partials.ad-slot', [
                'pageKey' => $pageKey,
                'positionKey' => $position,
                'variant' => $variant,
                'unitsHtml' => $html,
                'isPreview' => ads_preview_mode(),
                'previewLabel' => $previewLabel,
                'previewSource' => $source,
            ])->render();
        }

        return implode("\n", $renderedSlots);
    }

    /**
     * Cached header/footer custom code for the public layout.
     *
     * @return array{header_code: string, footer_code: string}
     */
    public function frontendCustomCode(?int $orgId = null): array
    {
        $orgId ??= current_organization_id();
        $cacheKey = 'frontend.ads.custom-code.'.($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($orgId) {
            return $this->customCode((int) ($orgId ?? 0));
        });
    }

    /**
     * Inject section-level ads into article HTML (before each H2).
     */
    public function injectIntoContent(string $html, string $context, ?int $orgId = null): string
    {
        if ($html === '') {
            return $html;
        }

        $pageKey = match ($context) {
            'news', 'news_detail' => 'news_detail',
            'blog', 'blog_detail' => 'blog_detail',
            default => $context,
        };

        $slot = $this->renderSlot($pageKey, 'before_h2', $orgId);
        if ($slot === '') {
            return $html;
        }

        $injected = preg_replace('/(<h2\b[^>]*>)/i', $slot.'$1', $html);

        return is_string($injected) ? $injected : $html;
    }

    public function forgetCache(?int $orgId = null): void
    {
        $orgId ??= current_organization_id();
        $suffix = $orgId ?? 'global';

        foreach (AdvertisementCatalog::pages() as $pageKey => $page) {
            foreach ($page['positions'] as $positionKey) {
                Cache::forget('frontend.ads.v2.'.$pageKey.'.'.$positionKey.'.'.$suffix);
            }
        }

        Cache::forget('frontend.ads.custom-code.'.$suffix);

        foreach ([
            'blog_detail_above_h1', 'blog_detail_after_first_paragraph', 'blog_detail_between_sections',
            'blog_detail_before_comments', 'blog_detail_sidebar_top', 'blog_detail_sidebar_middle',
            'blog_detail_sidebar_bottom', 'news_detail_above_h1', 'news_detail_after_first_paragraph',
            'news_detail_between_sections', 'news_detail_before_comments', 'news_detail_sidebar_top',
            'news_detail_sidebar_middle', 'news_detail_sidebar_bottom', 'question_list_inline',
            'exam_attempt_left', 'exam_attempt_right', 'exam_attempt_bottom', 'exam_result',
            'home_sidebar', 'exam_list', 'blog_list', 'news_list', 'footer',
        ] as $legacy) {
            Cache::forget('frontend.ads.'.$legacy.'.'.$suffix);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveSlotKeys(string $pageOrLegacy, ?string $positionKey): array
    {
        if ($positionKey !== null && $positionKey !== '') {
            return [$pageOrLegacy, $positionKey];
        }

        if (str_contains($pageOrLegacy, '.')) {
            [$page, $position] = explode('.', $pageOrLegacy, 2);

            return [$page, $position];
        }

        $legacy = [
            'blog_detail_above_h1' => ['blog_detail', 'after_header'],
            'blog_detail_before_h2' => ['blog_detail', 'before_h2'],
            'blog_detail_sidebar_top' => ['blog_detail', 'before_content'],
            'blog_detail_sidebar_middle' => ['blog_detail', 'between_sections'],
            'blog_detail_before_comments' => ['blog_detail', 'after_related'],
            'blog_detail_sidebar_bottom' => ['blog_detail', 'after_content'],
            'news_detail_above_h1' => ['news_detail', 'after_header'],
            'news_detail_before_h2' => ['news_detail', 'before_h2'],
            'news_detail_sidebar_top' => ['news_detail', 'before_content'],
            'news_detail_sidebar_middle' => ['news_detail', 'between_sections'],
            'news_detail_before_comments' => ['news_detail', 'after_content'],
            'news_detail_sidebar_bottom' => ['news_detail', 'after_content'],
            'exam_attempt_left' => ['exam_attempt', 'right_after_overview'],
            'exam_attempt_right' => ['exam_attempt', 'right_after_palette'],
            'exam_attempt_bottom' => ['exam_attempt', 'below_content'],
            'exam_result' => ['exam_result', 'below_title'],
            'question_list_inline' => ['question_list', 'below_items'],
            'home_sidebar' => ['home', 'after_header'],
            'exam_list' => ['exam_list', 'below_items'],
            'blog_list' => ['blog_list', 'below_items'],
            'news_list' => ['news_list', 'below_items'],
            'footer' => ['home', 'above_footer'],
        ];

        return $legacy[$pageOrLegacy] ?? ['', ''];
    }

    protected function renderPlacementUnit(AdPlacement $placement): string
    {
        if ($placement->isGoogle()) {
            $google = $placement->googleAdvertisement;
            if (! $google || $google->status !== 'active' || ! filled($google->code)) {
                return '';
            }

            return view('frontend.partials.ad-unit', [
                'source' => 'google',
                'name' => $google->name,
                'html' => $google->code,
                'css' => null,
                'js' => null,
            ])->render();
        }

        $ad = $placement->advertisement;
        if (! $ad || $ad->status !== 'active') {
            return '';
        }

        if ($ad->isHtml()) {
            return view('frontend.partials.ad-unit', [
                'source' => 'html',
                'name' => $ad->name,
                'html' => (string) $ad->html_code,
                'css' => $ad->css_code,
                'js' => $ad->js_code,
            ])->render();
        }

        if ($ad->isIframe() && filled($ad->iframe_url)) {
            $width = $ad->width ?: 300;
            $height = $ad->height ?: 250;
            $iframe = '<iframe src="'.e($ad->iframe_url).'" title="'.e($ad->title ?: $ad->name).'" '
                .'width="'.(int) $width.'" height="'.(int) $height.'" '
                .'loading="lazy" referrerpolicy="no-referrer-when-downgrade" '
                .($ad->is_responsive ? 'style="max-width:100%;width:100%;border:0;"' : 'style="border:0;"')
                .'></iframe>';

            return view('frontend.partials.ad-unit', [
                'source' => 'iframe',
                'name' => $ad->name,
                'html' => $iframe,
                'css' => null,
                'js' => null,
            ])->render();
        }

        if ($ad->isBanner()) {
            $url = $ad->image?->file_url;
            if (! $url) {
                return '';
            }
            $img = '<img src="'.e($url).'" alt="'.e($ad->title ?: $ad->name).'" loading="lazy" class="et-ad-unit__img">';
            if (filled($ad->target_url)) {
                $target = $ad->open_in_new_tab ? ' target="_blank" rel="noopener sponsored"' : ' rel="sponsored"';
                $img = '<a href="'.e($ad->target_url).'"'.$target.'>'.$img.'</a>';
            }

            return view('frontend.partials.ad-unit', [
                'source' => 'banner',
                'name' => $ad->name,
                'html' => $img,
                'css' => null,
                'js' => null,
            ])->render();
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeAdvertisementPayload(array $data): array
    {
        $type = $data['type'] ?? AdvertisementCatalog::TYPE_BANNER;
        $base = [
            'name' => $data['name'],
            'title' => $data['title'] ?? $data['name'],
            'type' => $type,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            'image_id' => null,
            'target_url' => null,
            'open_in_new_tab' => true,
            'banner_size' => null,
            'iframe_url' => null,
            'width' => null,
            'height' => null,
            'is_responsive' => true,
            'html_code' => null,
            'css_code' => null,
            'js_code' => null,
        ];

        return match ($type) {
            AdvertisementCatalog::TYPE_BANNER => array_merge($base, [
                'image_id' => $data['image_id'] ?? null,
                'target_url' => $data['target_url'] ?? null,
                'open_in_new_tab' => array_key_exists('open_in_new_tab', $data) ? (bool) $data['open_in_new_tab'] : true,
                'banner_size' => $data['banner_size'] ?? null,
            ]),
            AdvertisementCatalog::TYPE_IFRAME => array_merge($base, [
                'iframe_url' => $data['iframe_url'] ?? null,
                'width' => isset($data['width']) ? (int) $data['width'] : null,
                'height' => isset($data['height']) ? (int) $data['height'] : null,
                'is_responsive' => array_key_exists('is_responsive', $data) ? (bool) $data['is_responsive'] : true,
            ]),
            AdvertisementCatalog::TYPE_HTML => array_merge($base, [
                'html_code' => $data['html_code'] ?? null,
                'css_code' => $data['css_code'] ?? null,
                'js_code' => $data['js_code'] ?? null,
            ]),
            default => $base,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertPlacementPayload(array $data, int $orgId): void
    {
        $pageKey = $data['page_key'] ?? '';
        $positionKey = $data['position_key'] ?? '';
        $page = AdvertisementCatalog::page($pageKey);

        if (! $page) {
            throw ValidationException::withMessages(['page_key' => 'Invalid page selected.']);
        }

        if (! in_array($positionKey, $page['positions'], true)) {
            throw ValidationException::withMessages(['position_key' => 'Invalid placement position for this page.']);
        }

        $source = $data['source_type'] ?? '';
        if ($source === AdvertisementCatalog::SOURCE_CUSTOM) {
            $adId = (int) ($data['advertisement_id'] ?? 0);
            $exists = Advertisement::query()->forOrg($orgId)->whereKey($adId)->exists();
            if (! $exists) {
                throw ValidationException::withMessages(['advertisement_id' => 'Select a valid custom advertisement.']);
            }
        } elseif ($source === AdvertisementCatalog::SOURCE_GOOGLE) {
            $adId = (int) ($data['google_advertisement_id'] ?? 0);
            $exists = GoogleAdvertisement::query()->forOrg($orgId)->whereKey($adId)->exists();
            if (! $exists) {
                throw ValidationException::withMessages(['google_advertisement_id' => 'Select a valid Google Ad configuration.']);
            }
        } else {
            throw ValidationException::withMessages(['source_type' => 'Choose Google Ad or Custom Advertisement.']);
        }
    }
}

