<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\StoreOrganizationMemberRequest;
use App\Models\UserOrganization;
use App\Services\Settings\OrganizationMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationMemberController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected OrganizationMemberService $members,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->members->paginate($this->currentOrgId(), $request->only([
            'search', 'status', 'per_page',
        ]));

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()
                ->map(fn (UserOrganization $row) => $this->members->serialize($row))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function store(StoreOrganizationMemberRequest $request): JsonResponse
    {
        $member = $this->members->create($request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Member created.',
            'member' => $this->members->serialize($member),
        ], 201);
    }

    public function update(StoreOrganizationMemberRequest $request, UserOrganization $member): JsonResponse
    {
        $member = $this->members->update($member, $request->validated(), $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Member updated.',
            'member' => $this->members->serialize($member),
        ]);
    }

    public function destroy(UserOrganization $member): JsonResponse
    {
        $this->members->delete($member, $this->currentOrgId());

        return response()->json([
            'success' => true,
            'message' => 'Member removed.',
        ]);
    }
}
