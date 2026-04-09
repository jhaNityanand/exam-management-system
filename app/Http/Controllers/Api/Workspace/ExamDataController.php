<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 404);

        $query = Exam::query()->forOrg($orgId)->with(['category', 'createdBy']);

        DatatableQuery::apply($query, $request, ['title', 'description', 'status'], 'id');

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
