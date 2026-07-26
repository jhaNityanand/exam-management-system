<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Advertisement\StoreAdvertisementRequest;
use App\Models\Cms\Advertisement;
use App\Services\Advertisement\AdvertisementService;
use App\Support\AdvertisementCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

        return view('backend.advertisements.index', [
            'ads' => $this->service->listForAdmin($orgId, $request->only(['placement', 'type', 'status', 'search'])),
            'types' => AdvertisementCatalog::types(),
            'placements' => AdvertisementCatalog::placements(),
            'placementGroups' => AdvertisementCatalog::placementGroups(),
            'questionEveryN' => $this->service->questionListEveryN($orgId),
            'filters' => [
                'placement' => $request->string('placement')->toString(),
                'type' => $request->string('type')->toString(),
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('backend.advertisements.form', [
            'ad' => null,
            'types' => AdvertisementCatalog::types(),
            'placementGroups' => AdvertisementCatalog::placementGroups(),
        ]);
    }

    public function store(StoreAdvertisementRequest $request): RedirectResponse|JsonResponse
    {
        $ad = $this->service->create($request->validated(), $this->currentOrgId());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Advertisement created.',
                'ad' => $ad,
                'redirect' => route('admin.advertisements.index'),
            ]);
        }

        return redirect()
            ->route('admin.advertisements.index')
            ->with('success', 'Advertisement created.');
    }

    public function edit(Advertisement $advertisement): View
    {
        $this->authorizeAd($advertisement);

        return view('backend.advertisements.form', [
            'ad' => $advertisement->load(['image', 'mobileImage']),
            'types' => AdvertisementCatalog::types(),
            'placementGroups' => AdvertisementCatalog::placementGroups(),
        ]);
    }

    public function update(StoreAdvertisementRequest $request, Advertisement $advertisement): RedirectResponse|JsonResponse
    {
        $this->authorizeAd($advertisement);
        $ad = $this->service->update($advertisement, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Advertisement updated.',
                'ad' => $ad,
                'redirect' => route('admin.advertisements.index'),
            ]);
        }

        return redirect()
            ->route('admin.advertisements.index')
            ->with('success', 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse|JsonResponse
    {
        $this->authorizeAd($advertisement);
        $this->service->delete($advertisement);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Advertisement deleted.']);
        }

        return redirect()
            ->route('admin.advertisements.index')
            ->with('success', 'Advertisement deleted.');
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'question_list_every_n' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $this->service->updateSettings($data, $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Advertisement settings saved.',
            'question_list_every_n' => (int) $data['question_list_every_n'],
        ]);
    }

    protected function authorizeAd(Advertisement $ad): void
    {
        abort_unless((int) $ad->organization_id === $this->currentOrgId(), 404);
    }
}
