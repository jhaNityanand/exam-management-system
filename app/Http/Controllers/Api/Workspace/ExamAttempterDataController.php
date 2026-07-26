<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamAttempterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamAttempterDataController extends Controller
{
    public function __construct(
        protected ExamAttempterService $attempters,
    ) {}

    public function index(Request $request, Exam $exam): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $exam = $this->attempters->findExamForOrg((int) $exam->id, (int) $orgId);
        $paginator = $this->attempters->paginateAttempters($exam, $request);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
            ],
        ]);
    }

    public function attempts(Exam $exam, int $user): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $exam = $this->attempters->findExamForOrg((int) $exam->id, (int) $orgId);
        $candidate = $this->attempters->findUserWhoAttempted($exam, $user);
        $history = $this->attempters->attemptHistory($exam, $candidate);

        return response()->json([
            'data' => $history,
            'user' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
            ],
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
            ],
        ]);
    }

    public function verification(Exam $exam, int $user): JsonResponse
    {
        $orgId = current_organization_id();
        abort_if($orgId === null, 503, 'No organization found. Please run the database seeder.');

        $exam = $this->attempters->findExamForOrg((int) $exam->id, (int) $orgId);
        $candidate = $this->attempters->findUserWhoAttempted($exam, $user);
        $payload = $this->attempters->verificationPayload($exam, $candidate);

        return response()->json($payload);
    }
}
