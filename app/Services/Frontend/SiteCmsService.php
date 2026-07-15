<?php

namespace App\Services\Frontend;

use App\Models\Cms\Announcement;
use App\Models\Cms\HomeSection;
use App\Models\Cms\SiteMenu;
use App\Models\Cms\SiteSetting;
use App\Models\Cms\SocialLink;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SiteCmsService
{
    public function organizationId(): ?int
    {
        return current_organization_id();
    }

    public function organization(): ?Organization
    {
        $id = $this->organizationId();

        return $id ? Organization::query()->find($id) : Organization::query()->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(?int $orgId = null): array
    {
        $orgId ??= $this->organizationId();
        $cacheKey = 'frontend.site_settings.'.($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($orgId) {
            $query = SiteSetting::query();
            if ($orgId) {
                $query->where(function ($q) use ($orgId) {
                    $q->where('organization_id', $orgId)->orWhereNull('organization_id');
                });
            }

            $rows = $query->get();
            $mapped = [];
            foreach ($rows as $row) {
                $mapped[$row->group.'.'.$row->key] = $this->castSetting($row->value, $row->type);
            }

            return $mapped;
        });
    }

    public function setting(string $key, mixed $default = null, ?int $orgId = null): mixed
    {
        $all = $this->settings($orgId);

        return $all[$key] ?? $default;
    }

    /**
     * @return Collection<int, \App\Models\Cms\SiteMenuItem>
     */
    public function menuItems(string $location, ?int $orgId = null): Collection
    {
        $orgId ??= $this->organizationId();
        $cacheKey = "frontend.menu.{$location}.".($orgId ?? 'global');

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($location, $orgId) {
            $menu = SiteMenu::query()
                ->where('location', $location)
                ->where('status', 'active')
                ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                    $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
                }))
                ->with(['items' => fn ($q) => $q->visible()->ordered()->whereNull('parent_id')->with('childrenRecursive')])
                ->first();

            return $menu?->items ?? collect();
        });
    }

    /**
     * @return Collection<int, SocialLink>
     */
    public function socialLinks(?int $orgId = null): Collection
    {
        $orgId ??= $this->organizationId();

        return Cache::remember('frontend.social.'.($orgId ?? 'global'), now()->addMinutes(15), function () use ($orgId) {
            return SocialLink::query()
                ->visible()
                ->ordered()
                ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                    $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
                }))
                ->get();
        });
    }

    /**
     * @return Collection<int, HomeSection>
     */
    public function homeSections(?int $orgId = null): Collection
    {
        $orgId ??= $this->organizationId();

        return HomeSection::query()
            ->enabled()
            ->ordered()
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->get()
            ->keyBy('key');
    }

    /**
     * @return Collection<int, Announcement>
     */
    public function announcements(?int $orgId = null): Collection
    {
        $orgId ??= $this->organizationId();

        return Announcement::query()
            ->active()
            ->ordered()
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->limit(5)
            ->get();
    }

    public function clearCache(?int $orgId = null): void
    {
        $orgId ??= $this->organizationId();
        $suffix = $orgId ?? 'global';
        Cache::forget('frontend.site_settings.'.$suffix);
        Cache::forget('frontend.social.'.$suffix);
        foreach (['header', 'footer', 'footer_legal', 'mobile'] as $location) {
            Cache::forget("frontend.menu.{$location}.{$suffix}");
        }
    }

    protected function castSetting(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode((string) $value, true) ?: [],
            default => $value,
        };
    }
}
