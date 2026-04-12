<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 404);

        $query = Question::query()->forOrg($orgId)->with(['category', 'createdBy']);

        DatatableQuery::apply($query, $request, ['body', 'type', 'difficulty', 'status'], 'id');

        $categoryId = $request->query('filters.category_id');
        if ($categoryId) {
            $query->where('category_id', (int) $categoryId);
        }

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
