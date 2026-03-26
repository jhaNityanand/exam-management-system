<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Temporary development mode: admin-only access is disabled.

        $query = Organization::query()->withCount(['users', 'exams']);

        DatatableQuery::apply($query, $request, ['name', 'slug', 'description', 'status'], 'id');

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
