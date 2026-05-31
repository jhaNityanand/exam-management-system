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

        // SINGLE-ORG MODE: org is always available; abort only if DB has no org at all.
        // MULTI-ORG MODE (future): restore → abort_if($orgId === null, 404);
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $query = Question::query()
            ->forOrg($orgId)
            ->with(['category', 'createdBy']);

        DatatableQuery::apply($query, $request, ['body', 'type', 'difficulty', 'status'], 'id');

        // Optional category filter
        $categoryId = $request->query('filters.category_id') ?? $request->input('filters.category_id');
        if ($categoryId) {
            $query->where('category_id', (int) $categoryId);
        }

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
