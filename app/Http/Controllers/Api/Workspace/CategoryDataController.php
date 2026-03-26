<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('category.view'), 403);

        $orgId = current_organization_id();
        abort_if($orgId === null, 404);

        $query = Category::query()->forOrg($orgId)->with('parent');

        $mainOnly = filter_var($request->query('main_only', false), FILTER_VALIDATE_BOOLEAN);
        if ($mainOnly) {
            $query->roots();
        }

        DatatableQuery::apply($query, $request, ['name', 'description', 'status'], 'name');

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
