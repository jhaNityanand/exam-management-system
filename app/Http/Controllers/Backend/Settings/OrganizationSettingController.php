<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\StoreHeroBannerRequest;
use App\Http\Requests\Backend\Settings\UpdateOrganizationSettingRequest;
use App\Services\Settings\FaqSettingsService;
use App\Services\Settings\OrganizationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationSettingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected OrganizationSettingsService $settings,
        protected FaqSettingsService $faqs,
    ) {}

    public function edit(): View
    {
        $orgId = $this->currentOrgId();
        $payload = $this->settings->get($orgId);

        return view('backend.settings.organization', [
            'payload' => $payload,
            'platforms' => OrganizationSettingsService::SOCIAL_PLATFORMS,
            'faqCategories' => $this->faqs->categories($orgId),
        ]);
    }

    public function update(UpdateOrganizationSettingRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $payload = $this->settings->update($request->validated(), $orgId);

        return response()->json([
            'success' => true,
            'message' => 'Organization settings saved.',
            'payload' => $payload,
        ]);
    }

    public function storeHero(StoreHeroBannerRequest $request): JsonResponse
    {
        $hero = $this->settings->saveHero($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Hero banner created.',
            'hero' => $this->serializeHero($hero),
            'heroes' => $this->settings->heroes($this->currentOrgId())->map(fn ($h) => $this->serializeHero($h))->values(),
        ]);
    }

    public function updateHero(StoreHeroBannerRequest $request, int $hero): JsonResponse
    {
        $saved = $this->settings->saveHero($request->validated(), $this->currentOrgId(), $hero);

        return response()->json([
            'success' => true,
            'message' => 'Hero banner updated.',
            'hero' => $this->serializeHero($saved),
            'heroes' => $this->settings->heroes($this->currentOrgId())->map(fn ($h) => $this->serializeHero($h))->values(),
        ]);
    }

    public function destroyHero(int $hero): JsonResponse
    {
        $this->settings->deleteHero($hero, $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Hero banner deleted.',
            'heroes' => $this->settings->heroes($this->currentOrgId())->map(fn ($h) => $this->serializeHero($h))->values(),
        ]);
    }

    public function reorderHeroes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer'],
        ]);

        $this->settings->reorderHeroes($data['order'], $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Hero order updated.',
            'heroes' => $this->settings->heroes($this->currentOrgId())->map(fn ($h) => $this->serializeHero($h))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeHero(\App\Models\Cms\HeroBanner $hero): array
    {
        return [
            'id' => $hero->id,
            'title' => $hero->title,
            'subtitle' => $hero->subtitle,
            'description' => $hero->description,
            'badge_text' => $hero->badge_text,
            'primary_cta_label' => $hero->primary_cta_label,
            'primary_cta_url' => $hero->primary_cta_url,
            'secondary_cta_label' => $hero->secondary_cta_label,
            'secondary_cta_url' => $hero->secondary_cta_url,
            'image_id' => $hero->image_id,
            'mobile_image_id' => $hero->mobile_image_id,
            'image_url' => $hero->image?->file_url,
            'mobile_image_url' => $hero->mobileImage?->file_url,
            'theme' => $hero->theme,
            'show_search' => (bool) $hero->show_search,
            'sort_order' => (int) $hero->sort_order,
            'status' => $hero->status,
            'starts_at' => optional($hero->starts_at)->format('Y-m-d\TH:i'),
            'ends_at' => optional($hero->ends_at)->format('Y-m-d\TH:i'),
        ];
    }
}
