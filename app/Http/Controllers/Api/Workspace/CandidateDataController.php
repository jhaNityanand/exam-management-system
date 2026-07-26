<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\CandidateService;
use App\Services\ProfileAvatarService;
use App\Support\DatatableQuery;
use App\Support\DateRangeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateDataController extends Controller
{
    private const ALLOWED_SORTS = [
        'id',
        'name',
        'email',
        'username',
        'status',
        'created_at',
        'updated_at',
        'attempts_count',
    ];

    private const ALLOWED_FILTERS = [
        'status',
    ];

    public function __construct(
        protected CandidateService $candidates,
        protected ProfileAvatarService $avatars,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $trash = (string) data_get($request->query('filters', []), 'trash', 'active');
        $sort = (string) $request->query('sort', 'id');
        if (! in_array($sort, self::ALLOWED_SORTS, true)) {
            $request->query->set('sort', 'id');
        }

        $filters = $request->query('filters', []);
        $createdFrom = is_array($filters) ? ($filters['created_from'] ?? null) : null;
        $createdTo = is_array($filters) ? ($filters['created_to'] ?? null) : null;
        $emailVerified = is_array($filters) ? ($filters['email_verified'] ?? null) : null;
        $examId = is_array($filters) ? (int) ($filters['exam_id'] ?? 0) : 0;

        if ($examId > 0) {
            $examExists = Exam::query()
                ->forOrg((int) $orgId)
                ->whereKey($examId)
                ->exists();
            if (! $examExists) {
                $examId = 0;
            }
        }

        if (is_array($filters)) {
            $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
            $request->query->set('filters', $filters);
        }

        $query = $this->candidates->queryForOrganization(
            (int) $orgId,
            $trash === 'bin' ? 'bin' : 'active',
            $examId > 0 ? $examId : null,
        );

        DateRangeFilter::apply($query, 'created_at', $createdFrom, $createdTo);

        if ($emailVerified === '1' || $emailVerified === 1 || $emailVerified === true) {
            $query->whereNotNull('email_verified_at');
        } elseif ($emailVerified === '0' || $emailVerified === 0) {
            $query->whereNull('email_verified_at');
        }

        DatatableQuery::apply(
            $query,
            $request,
            ['name', 'email', 'username', 'status'],
            'id'
        );

        $paginator = $query->paginate(DatatableQuery::perPage($request));

        $data = collect($paginator->items())->map(function ($user) {
            $role = $user->organizations->first()?->pivot?->role;
            $avatar = \App\Support\UserAvatar::resolve($user);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $role,
                'attempts_count' => (int) ($user->attempts_count ?? 0),
                'email_verified_at' => optional($user->email_verified_at)->toIso8601String(),
                'phone' => $user->profile?->phone,
                'gender' => $user->profile?->gender,
                'avatar_url' => $avatar['url'],
                'initials' => $avatar['initials'],
                'avatar_color' => $avatar['color'],
                'created_at' => optional($user->created_at)->toIso8601String(),
                'updated_at' => optional($user->updated_at)->toIso8601String(),
                'deleted_at' => optional($user->deleted_at)->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'exam_id' => $examId > 0 ? $examId : null,
            ],
        ]);
    }
}
