<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Advertisement\StoreAdPlacementRequest;
use App\Http\Requests\Backend\Advertisement\StoreAdvertisementRequest;
use App\Http\Requests\Backend\Advertisement\StoreGoogleAdvertisementRequest;
use App\Http\Requests\Backend\Advertisement\UpdateAdCustomCodeRequest;
use App\Http\Requests\Backend\Advertisement\UpdateAdPlacementRequest;
use App\Models\Cms\AdPlacement;
use App\Models\Cms\Advertisement;
use App\Models\Cms\GoogleAdvertisement;
use App\Services\Advertisement\AdvertisementService;
use App\Support\AdvertisementCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected AdvertisementService $service,
    ) {}

    public function index(Request $request): View
    {
        $orgId = $this->currentOrgId();
        $pageKey = $request->string('page')->toString() ?: AdvertisementCatalog::defaultPageKey();
        if (! AdvertisementCatalog::page($pageKey)) {
            $pageKey = AdvertisementCatalog::defaultPageKey();
        }

        $ads = $this->service->listAdvertisements($orgId);
        $googleAds = $this->service->listGoogleAds($orgId);
        $placements = $this->service->placementsForPage($orgId, $pageKey);

        return view('backend.advertisements.index', [
            'pageKey' => $pageKey,
            'pages' => AdvertisementCatalog::pages(),
            'pagesGrouped' => AdvertisementCatalog::pagesGrouped(),
            'positions' => AdvertisementCatalog::positions(),
            'types' => AdvertisementCatalog::types(),
            'bannerSizes' => AdvertisementCatalog::bannerSizes(),
            'multiPositions' => AdvertisementCatalog::MULTI_POSITIONS,
            'ads' => $ads,
            'googleAds' => $googleAds,
            'placements' => $placements,
            'customCode' => $this->service->customCode($orgId),
            'adsPayload' => $ads->map->toAdminArray()->values(),
            'googleAdsPayload' => $googleAds->map->toAdminArray()->values(),
            'placementsPayload' => $placements->map->toAdminArray()->values(),
            'galleryDataUrl' => route('admin.gallery.data'),
            'galleryStoreUrl' => route('admin.gallery.store'),
            'galleryCommitUrl' => route('admin.gallery.commit'),
        ]);
    }

    public function placements(Request $request): JsonResponse
    {
        $pageKey = $request->string('page')->toString() ?: AdvertisementCatalog::defaultPageKey();
        if (! AdvertisementCatalog::page($pageKey)) {
            return response()->json(['success' => false, 'message' => 'Invalid page.'], 422);
        }

        $placements = $this->service->placementsForPage($this->currentOrgId(), $pageKey);

        return response()->json([
            'success' => true,
            'page_key' => $pageKey,
            'page' => AdvertisementCatalog::page($pageKey),
            'placements' => $placements->map->toAdminArray()->values(),
        ]);
    }

    public function storePlacement(StoreAdPlacementRequest $request): JsonResponse
    {
        $placement = $this->service->createPlacement($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Advertisement placed successfully.',
            'placement' => $placement->toAdminArray(),
        ], 201);
    }

    public function updatePlacement(UpdateAdPlacementRequest $request, AdPlacement $placement): JsonResponse
    {
        $this->authorizePlacement($placement);
        $updated = $this->service->updatePlacement($placement, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Placement updated.',
            'placement' => $updated->toAdminArray(),
        ]);
    }

    public function destroyPlacement(AdPlacement $placement): JsonResponse
    {
        $this->authorizePlacement($placement);
        $this->service->deletePlacement($placement);

        return response()->json([
            'success' => true,
            'message' => 'Placement removed.',
        ]);
    }

    public function store(StoreAdvertisementRequest $request): JsonResponse
    {
        $ad = $this->service->createAdvertisement($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created.',
            'ad' => $ad->toAdminArray(),
        ], 201);
    }

    public function update(StoreAdvertisementRequest $request, Advertisement $advertisement): JsonResponse
    {
        $this->authorizeAd($advertisement);
        $ad = $this->service->updateAdvertisement($advertisement, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated.',
            'ad' => $ad->toAdminArray(),
        ]);
    }

    public function destroy(Advertisement $advertisement): JsonResponse
    {
        $this->authorizeAd($advertisement);
        $this->service->deleteAdvertisement($advertisement);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted.',
        ]);
    }

    public function storeGoogle(StoreGoogleAdvertisementRequest $request): JsonResponse
    {
        $ad = $this->service->createGoogleAd($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Google Ad configuration created.',
            'google_ad' => $ad->toAdminArray(),
        ], 201);
    }

    public function updateGoogle(StoreGoogleAdvertisementRequest $request, GoogleAdvertisement $googleAdvertisement): JsonResponse
    {
        $this->authorizeGoogleAd($googleAdvertisement);
        $ad = $this->service->updateGoogleAd($googleAdvertisement, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Google Ad configuration updated.',
            'google_ad' => $ad->toAdminArray(),
        ]);
    }

    public function destroyGoogle(GoogleAdvertisement $googleAdvertisement): JsonResponse
    {
        $this->authorizeGoogleAd($googleAdvertisement);
        $this->service->deleteGoogleAd($googleAdvertisement);

        return response()->json([
            'success' => true,
            'message' => 'Google Ad configuration deleted.',
        ]);
    }

    public function updateCustomCode(UpdateAdCustomCodeRequest $request): JsonResponse
    {
        $code = $this->service->updateCustomCode($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Custom advertisement code saved.',
            'custom_code' => $code,
        ]);
    }

    protected function authorizeAd(Advertisement $ad): void
    {
        abort_unless((int) $ad->organization_id === $this->currentOrgId(), 404);
    }

    protected function authorizeGoogleAd(GoogleAdvertisement $ad): void
    {
        abort_unless((int) $ad->organization_id === $this->currentOrgId(), 404);
    }

    protected function authorizePlacement(AdPlacement $placement): void
    {
        abort_unless((int) $placement->organization_id === $this->currentOrgId(), 404);
    }
}
