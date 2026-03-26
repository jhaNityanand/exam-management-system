<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('member.view'), 403);

        $orgId = current_organization_id();
        abort_if($orgId === null, 404);

        $query = User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId))
            ->with(['organizations' => fn ($q) => $q->where('organizations.id', $orgId)]);

        DatatableQuery::apply($query, $request, ['name', 'email'], 'name');

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        $data = collect($paginator->items())->map(function (User $user) use ($orgId) {
            $pivot = $user->organizations->firstWhere('id', $orgId)?->pivot;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $pivot?->role,
                'status' => $pivot?->status,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
