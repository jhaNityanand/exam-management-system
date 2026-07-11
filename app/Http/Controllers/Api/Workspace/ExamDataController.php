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

        // SINGLE-ORG MODE: org is always available; abort only if DB has no org at all.
        // MULTI-ORG MODE (future): restore → abort_if($orgId === null, 404);
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $query = Exam::query()
            ->forOrg($orgId)
            ->with(['category', 'createdBy'])
            ->withCount('questions');

        DatatableQuery::apply($query, $request, ['title', 'description', 'status'], 'id');

        // Build a lightweight stats query (no withCount — avoids MySQL ONLY_FULL_GROUP_BY)
        $baseStatsQuery = Exam::query()->forOrg($orgId);
        DatatableQuery::apply($baseStatsQuery, $request, ['title', 'description', 'status'], 'id');
        $baseStatsQuery->getQuery()->orders = null;
        $baseStatsQuery->getQuery()->limit  = null;
        $baseStatsQuery->getQuery()->offset = null;

        $statusCounts = (clone $baseStatsQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $avgDuration = (clone $baseStatsQuery)->avg('duration') ?? 0;

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'stats' => [
                'total'        => $paginator->total(),
                'published'    => $statusCounts->get('published', 0),
                'draft'        => $statusCounts->get('draft', 0),
                'active'       => $statusCounts->get('active', 0),
                'avg_duration' => round($avgDuration),
            ]
        ]);
    }
}
