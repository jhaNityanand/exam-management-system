<?php

namespace App\Services\Advertisement;

use App\Models\Cms\Advertisement;
use App\Models\Cms\SiteSetting;
use App\Services\Frontend\SiteCmsService;
use App\Support\AdvertisementCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
    public function listForAdmin(int $orgId, array $filters = []): Collection
    {
        return Advertisement::query()
            ->forOrg($orgId)
            ->with(['image', 'mobileImage'])
            ->when($filters['placement'] ?? null, fn ($q, $placement) => $q->where('placement', $placement))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('headline', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $orgId): Advertisement
    {
        $data['organization_id'] = $orgId;
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = (int) Advertisement::query()->forOrg($orgId)->max('sort_order') + 1;
        }

        $ad = Advertisement::query()->create($data);
        $this->forgetCache($orgId);

        return $ad->load(['image', 'mobileImage']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Advertisement $ad, array $data): Advertisement
    {
        $ad->update($data);
        $this->forgetCache((int) $ad->organization_id);

        return $ad->fresh(['image', 'mobileImage']);
    }

    public function delete(Advertisement $ad): void
    {
        $orgId = (int) $ad->organization_id;
        $ad->delete();
        $this->forgetCache($orgId);
    }

    /**
     * Active ads for a placement (org + global fallback).
     *
     * @return Collection<int, Advertisement>
     */
    public function forPlacement(string $placement, ?int $orgId = null): Collection
    {
        $orgId ??= current_organization_id();
        $cacheKey = 'frontend.ads.'.$placement.'.'.($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($placement, $orgId) {
            return Advertisement::query()
                ->active($placement)
                ->ordered()
                ->with(['image', 'mobileImage'])
                ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                    $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
                }))
                ->get();
        });
    }

    public function firstForPlacement(string $placement, ?int $orgId = null): ?Advertisement
    {
        return $this->forPlacement($placement, $orgId)->first();
    }

    /**
     * Inject in-content ad slots into HTML body.
     */
    public function injectIntoContent(string $html, string $context, ?int $orgId = null): string
    {
        $prefix = $context === 'news' ? 'news_detail' : 'blog_detail';
        $afterFirst = $this->renderSlot($prefix.'_after_first_paragraph', $orgId);
        $between = $this->renderSlot($prefix.'_between_sections', $orgId);

        if ($afterFirst === '' && $between === '') {
            return $html;
        }

        // After first paragraph
        if ($afterFirst !== '' && preg_match('/<\/p>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $html = substr($html, 0, $pos).$afterFirst.substr($html, $pos);
        }

        // Between sections: after roughly the middle heading or mid-point paragraph
        if ($between !== '') {
            if (preg_match_all('/<\/(h2|h3|p)>/i', $html, $matches, PREG_OFFSET_CAPTURE) && count($matches[0]) >= 4) {
                $midIndex = (int) floor(count($matches[0]) / 2);
                $match = $matches[0][$midIndex];
                $pos = $match[1] + strlen($match[0]);
                // Avoid double-inserting at the same first-paragraph boundary
                if ($afterFirst === '' || $pos > (strpos($html, $afterFirst) ?: 0) + strlen($afterFirst)) {
                    $html = substr($html, 0, $pos).$between.substr($html, $pos);
                }
            }
        }

        return $html;
    }

    public function renderSlot(string $placement, ?int $orgId = null): string
    {
        $ads = $this->forPlacement($placement, $orgId);
        if ($ads->isEmpty()) {
            return '';
        }

        return view('frontend.partials.ads', [
            'ads' => $ads,
            'placement' => $placement,
        ])->render();
    }

    public function questionListEveryN(?int $orgId = null): int
    {
        $value = (int) $this->siteCms->setting(self::SETTINGS_GROUP.'.question_list_every_n', 2, $orgId);

        return max(0, min(50, $value));
    }

    public function updateSettings(array $data, int $orgId): void
    {
        $map = [
            'question_list_every_n' => ['type' => 'integer', 'label' => 'Insert ad every N questions'],
        ];

        foreach ($map as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            SiteSetting::query()->updateOrCreate(
                ['organization_id' => $orgId, 'group' => self::SETTINGS_GROUP, 'key' => $key],
                [
                    'value' => (string) (int) $data[$key],
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                ]
            );
        }

        $this->siteCms->clearCache($orgId);
        $this->forgetCache($orgId);
    }

    public function seedDefaults(int $orgId): void
    {
        SiteSetting::query()->updateOrCreate(
            ['organization_id' => $orgId, 'group' => self::SETTINGS_GROUP, 'key' => 'question_list_every_n'],
            [
                'value' => '2',
                'type' => 'integer',
                'label' => 'Insert ad every N questions',
            ]
        );

        $samples = [
            [
                'name' => 'Blog sidebar promo',
                'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
                'placement' => 'blog_detail_sidebar_top',
                'code' => '<div style="padding:1rem;text-align:center;background:#f1f5f9;border-radius:8px;font:600 14px/1.4 system-ui,sans-serif;color:#0f172a">Sponsored · Try Examtube premium mocks</div>',
                'sort_order' => 1,
            ],
            [
                'name' => 'Question list inline',
                'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
                'placement' => 'question_list_inline',
                'code' => '<div style="padding:1rem;text-align:center;background:#ecfeff;border-radius:8px;font:600 14px/1.4 system-ui,sans-serif;color:#155e75">Ad slot · Practice more with Examtube</div>',
                'sort_order' => 1,
            ],
            [
                'name' => 'Footer strip',
                'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
                'placement' => 'footer',
                'code' => '<div style="padding:.85rem;text-align:center;font:500 13px/1.4 system-ui,sans-serif;color:#64748b">Advertisement placeholder — replace with AdSense or a banner</div>',
                'sort_order' => 1,
            ],
        ];

        foreach ($samples as $sample) {
            $exists = Advertisement::query()
                ->forOrg($orgId)
                ->where('placement', $sample['placement'])
                ->where('name', $sample['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            Advertisement::query()->create([
                'organization_id' => $orgId,
                'name' => $sample['name'],
                'type' => $sample['type'],
                'placement' => $sample['placement'],
                'code' => $sample['code'],
                'sort_order' => $sample['sort_order'],
                'status' => 'inactive',
            ]);
        }

        $this->forgetCache($orgId);
    }

    public function forgetCache(?int $orgId = null): void
    {
        $orgId ??= current_organization_id();
        $suffix = $orgId ?? 'global';
        foreach (AdvertisementCatalog::placementKeys() as $placement) {
            Cache::forget('frontend.ads.'.$placement.'.'.$suffix);
        }
    }
}
