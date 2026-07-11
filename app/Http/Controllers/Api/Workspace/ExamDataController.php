<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Support\DatatableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamDataController extends Controller
{
    private const ALLOWED_SORTS = [
        'id',
        'title',
        'updated_at',
        'created_at',
        'duration',
        'pass_percentage',
        'questions_count',
        'status',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();

        // SINGLE-ORG MODE: org is always available; abort only if DB has no org at all.
        // MULTI-ORG MODE (future): restore → abort_if($orgId === null, 404);
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $this->normalizeSort($request);

        $query = Exam::query()
            ->forOrg($orgId)
            ->with(['category', 'createdBy'])
            ->withCount('questions');

        $this->applySearch($query, $request);

        // Search handled above — pass empty searchable columns to avoid double-applying.
        $search = $request->query('search');
        $request->query->set('search', '');
        DatatableQuery::apply($query, $request, [], 'updated_at');
        if ($search !== null) {
            $request->query->set('search', $search);
        }

        // Build a lightweight stats query (no withCount — avoids MySQL ONLY_FULL_GROUP_BY)
        $baseStatsQuery = Exam::query()->forOrg($orgId);
        $this->applySearch($baseStatsQuery, $request);
        $request->query->set('search', '');
        DatatableQuery::apply($baseStatsQuery, $request, [], 'updated_at');
        if ($search !== null) {
            $request->query->set('search', $search);
        }
        $baseStatsQuery->getQuery()->orders = null;
        $baseStatsQuery->getQuery()->limit = null;
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
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => [
                'total' => $paginator->total(),
                'published' => $statusCounts->get('published', 0),
                'draft' => $statusCounts->get('draft', 0),
                'active' => $statusCounts->get('active', 0),
                'avg_duration' => round($avgDuration),
            ],
        ]);
    }

    private function normalizeSort(Request $request): void
    {
        $sort = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $request->query('sort', 'updated_at'));
        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $request->query->set('sort', 'updated_at');
        }
    }

    private function applySearch(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhere('status', 'like', '%'.$search.'%')
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('createdBy', fn (Builder $u) => $u->where('name', 'like', '%'.$search.'%'));
        });
    }
}
