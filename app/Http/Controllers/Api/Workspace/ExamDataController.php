<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Support\DatatableQuery;
use App\Support\DateRangeFilter;
use App\Support\ExamFormOptions;
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
        'total_marks',
        'parts_count',
        'status',
        'scheduled_start',
    ];

    private const ALLOWED_FILTERS = [
        'category_id',
        'status',
        'exam_mode',
        'difficulty_level',
        'visibility',
        'exam_format',
        'created_by',
        'questions_min',
        'questions_max',
        'parts_min',
        'parts_max',
        'marks_min',
        'marks_max',
        'duration_min',
        'duration_max',
        'created_from',
        'created_to',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();

        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $trash = (string) data_get($request->query('filters', []), 'trash', 'active');
        $this->normalizeSort($request);
        $specialFilters = $this->normalizeFilters($request);

        $query = Exam::query();
        if ($trash === 'bin') {
            $query->onlyTrashed();
        }
        $query->forOrg($orgId)
            ->withCount('parts')
            ->with([
                'category:id,name',
                'createdBy:id,name',
                'parts:id,exam_id,name',
            ]);

        $this->applySearch($query, $request);
        $this->applySpecialFilters($query, $specialFilters);

        $search = $request->query('search');
        $request->query->set('search', '');
        DatatableQuery::apply($query, $request, [], 'updated_at');
        if ($search !== null) {
            $request->query->set('search', $search);
        }

        $baseStatsQuery = Exam::query();
        if ($trash === 'bin') {
            $baseStatsQuery->onlyTrashed();
        }
        $baseStatsQuery->forOrg($orgId);
        $this->applySearch($baseStatsQuery, $request);
        $this->applySpecialFilters($baseStatsQuery, $specialFilters);
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
        $avgQuestions = (clone $baseStatsQuery)->avg('total_questions') ?? 0;
        $partsAggregate = (clone $baseStatsQuery)
            ->leftJoin('exam_parts', function ($join) {
                $join->on('exam_parts.exam_id', '=', 'exams.id')
                    ->whereNull('exam_parts.deleted_at');
            })
            ->selectRaw('COUNT(DISTINCT exams.id) as exam_total, COUNT(exam_parts.id) as parts_total')
            ->first();
        $examTotalForParts = (int) ($partsAggregate->exam_total ?? 0);
        $avgParts = $examTotalForParts > 0
            ? round(((int) ($partsAggregate->parts_total ?? 0)) / $examTotalForParts, 1)
            : 0;

        $paginator = $query->paginate(DatatableQuery::perPage($request));
        $paginator->getCollection()->transform(function (Exam $exam) {
            $exam->setAttribute('questions_count', (int) ($exam->total_questions ?? 0));
            $exam->setAttribute('parts_count', (int) ($exam->parts_count ?? $exam->parts->count()));
            $exam->setAttribute('part_names', $exam->parts->pluck('name')->filter()->values()->all());

            return $exam;
        });

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
                'avg_duration' => round((float) $avgDuration),
                'avg_questions' => round((float) $avgQuestions),
                'avg_parts' => round((float) $avgParts, 1),
            ],
        ]);
    }

    private function normalizeSort(Request $request): void
    {
        $sort = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $request->query('sort', 'updated_at'));
        if ($sort === 'questions_count') {
            $sort = 'total_questions';
        }
        if ($sort === 'parts_count') {
            // withCount alias — DatatableQuery sorts by column name on the query.
            $request->query->set('sort', 'parts_count');

            return;
        }
        if (! in_array($sort, self::ALLOWED_SORTS, true) && $sort !== 'total_questions') {
            $request->query->set('sort', 'updated_at');
        } else {
            $request->query->set('sort', $sort);
        }
    }

    /**
     * Whitelist filters and pull out special keys that DatatableQuery cannot handle.
     *
     * @return array<string, mixed>
     */
    private function normalizeFilters(Request $request): array
    {
        $filters = $request->query('filters', []);
        if (! is_array($filters)) {
            $filters = [];
        }

        $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
        $special = [];

        foreach ([
            'exam_format',
            'questions_min',
            'questions_max',
            'parts_min',
            'parts_max',
            'marks_min',
            'marks_max',
            'duration_min',
            'duration_max',
            'created_from',
            'created_to',
            'created_by',
        ] as $key) {
            if (array_key_exists($key, $filters)) {
                $special[$key] = $filters[$key];
                unset($filters[$key]);
            }
        }

        if (isset($filters['category_id'])) {
            $ids = is_array($filters['category_id']) ? $filters['category_id'] : [$filters['category_id']];
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if ($ids === []) {
                unset($filters['category_id']);
            } else {
                $filters['category_id'] = count($ids) === 1 ? $ids[0] : $ids;
            }
        }

        $request->query->set('filters', $filters);

        return $special;
    }

    /**
     * @param  array<string, mixed>  $special
     */
    private function applySpecialFilters(Builder $query, array $special): void
    {
        if (! empty($special['exam_format'])) {
            $formats = is_array($special['exam_format']) ? $special['exam_format'] : [$special['exam_format']];
            $allowed = ExamFormOptions::examFormatIds();
            $formats = array_values(array_intersect($formats, $allowed));

            if ($formats !== []) {
                $query->where(function (Builder $q) use ($formats) {
                    foreach ($formats as $format) {
                        $q->orWhereJsonContains('exam_format', $format);
                    }
                });
            }
        }

        if (isset($special['created_by']) && $special['created_by'] !== '') {
            $query->where('created_by', (int) $special['created_by']);
        }

        // Parts architecture: filter on rolled-up exam totals, not a missing questions() relation.
        if (isset($special['questions_min']) && $special['questions_min'] !== '') {
            $query->where('total_questions', '>=', (int) $special['questions_min']);
        }

        if (isset($special['questions_max']) && $special['questions_max'] !== '') {
            $query->where('total_questions', '<=', (int) $special['questions_max']);
        }

        if (isset($special['marks_min']) && $special['marks_min'] !== '') {
            $query->where('total_marks', '>=', (int) $special['marks_min']);
        }

        if (isset($special['marks_max']) && $special['marks_max'] !== '') {
            $query->where('total_marks', '<=', (int) $special['marks_max']);
        }

        if (isset($special['parts_min']) && $special['parts_min'] !== '') {
            $query->has('parts', '>=', (int) $special['parts_min']);
        }

        if (isset($special['parts_max']) && $special['parts_max'] !== '') {
            $query->has('parts', '<=', (int) $special['parts_max']);
        }

        if (isset($special['duration_min']) && $special['duration_min'] !== '') {
            $query->where('duration', '>=', (int) $special['duration_min']);
        }

        if (isset($special['duration_max']) && $special['duration_max'] !== '') {
            $query->where('duration', '<=', (int) $special['duration_max']);
        }

        DateRangeFilter::apply(
            $query,
            'created_at',
            $special['created_from'] ?? null,
            $special['created_to'] ?? null
        );
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
                ->orWhereHas('createdBy', fn (Builder $u) => $u->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('parts', fn (Builder $p) => $p->where('name', 'like', '%'.$search.'%'));
        });
    }
}
