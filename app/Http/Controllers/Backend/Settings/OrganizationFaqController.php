<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\StoreFaqRequest;
use App\Models\Cms\Faq;
use App\Services\Settings\FaqSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationFaqController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected FaqSettingsService $faqs,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->faqs->paginate($this->currentOrgId(), $request->only([
            'search', 'status', 'category_id', 'per_page',
        ]));

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (Faq $faq) => $this->faqs->serialize($faq))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'categories' => $this->faqs->categories($this->currentOrgId())->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
            ])->values(),
        ]);
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = $this->faqs->create($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'FAQ created.',
            'faq' => $this->faqs->serialize($faq),
        ], 201);
    }

    public function update(StoreFaqRequest $request, Faq $faq): JsonResponse
    {
        $faq = $this->faqs->update($faq, $request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated.',
            'faq' => $this->faqs->serialize($faq),
        ]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $this->faqs->delete($faq, $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted.',
        ]);
    }
}
