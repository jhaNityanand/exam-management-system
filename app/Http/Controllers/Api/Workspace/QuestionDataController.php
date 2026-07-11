<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Support\DatatableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionDataController extends Controller
{
    private const ALLOWED_SORTS = [
        'id',
        'type',
        'difficulty',
        'marks',
        'status',
        'created_at',
        'updated_at',
        'body',
    ];

    private const ALLOWED_FILTERS = [
        'type',
        'difficulty',
        'status',
        'marks_type',
        'marks',
        'category_id',
        'allows_multiple',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();

        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $sort = (string) $request->query('sort', 'id');
        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $request->query->set('sort', 'id');
        }

        $filters = $request->query('filters', []);
        if (is_array($filters)) {
            $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));

            if (isset($filters['marks'])) {
                $marks = is_array($filters['marks']) ? $filters['marks'] : [$filters['marks']];
                $marks = array_values(array_unique(array_filter(
                    array_map('intval', $marks),
                    fn (int $m) => $m >= 1 && $m <= 10
                )));

                if ($marks === []) {
                    unset($filters['marks']);
                } else {
                    $filters['marks'] = count($marks) === 1 ? $marks[0] : $marks;
                }
            }

            if (isset($filters['category_id'])) {
                $categoryIds = is_array($filters['category_id'])
                    ? $filters['category_id']
                    : [$filters['category_id']];
                $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

                if ($categoryIds === []) {
                    unset($filters['category_id']);
                } else {
                    // Expand parents to include nested children
                    $expanded = $categoryIds;
                    $toProcess = $categoryIds;
                    while ($toProcess !== []) {
                        $children = \App\Models\QuestionCategory::query()
                            ->whereIn('parent_id', $toProcess)
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->all();
                        $new = array_values(array_diff($children, $expanded));
                        $expanded = array_values(array_unique(array_merge($expanded, $new)));
                        $toProcess = $new;
                    }
                    $filters['category_id'] = count($expanded) === 1 ? $expanded[0] : $expanded;
                }
            }

            $request->query->set('filters', $filters);
        }

        $query = Question::query()
            ->forOrg($orgId)
            ->with(['category', 'createdBy']);

        DatatableQuery::apply(
            $query,
            $request,
            ['body', 'type', 'difficulty', 'status', 'reference'],
            'id'
        );

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
