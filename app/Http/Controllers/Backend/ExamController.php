<?php

namespace App\Http\Controllers\Backend;

use App\Exceptions\AttemptQuestionShortageException;
use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Exam\StoreExamRequest;
use App\Http\Requests\Backend\Exam\UpdateExamRequest;
use App\Models\Exam;
use App\Services\ExamAttemptService;
use App\Services\ExamService;
use App\Services\QuestionBankService;
use App\Support\ExamFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected ExamService $examService,
        protected QuestionBankService $questionBankService,
        protected ExamAttemptService $examAttemptService,
    ) {}

    // ── List ──────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $orgId = $this->currentOrgId();
        $categories = app(\App\Services\ExamCategoryService::class)->getHierarchicalList($orgId);

        return view('backend.exams.index', compact('categories'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        $orgId = $this->currentOrgId();
        $categories = app(\App\Services\ExamCategoryService::class)->getHierarchicalList($orgId);
        $formOptions = ExamFormOptions::all($orgId);

        return view('backend.exams.create', compact('categories', 'formOptions'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Attach current organisation
        $data['organization_id'] = $this->currentOrgId();

        $exam = $this->examService->create($data);

        return redirect()
            ->route('admin.exams.show', $exam)
            ->with('success', 'Exam created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show($id): View
    {
        $exam = $this->findExamOrFail((int) $id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');

        $stats = $this->examService->getAttemptStats($exam);
        $questions = $exam->questions;

        $difficultyDistribution = $questions
            ->groupBy(fn ($q) => $q->difficulty ?: 'unknown')
            ->map->count()
            ->sortKeys();

        $typeDistribution = $questions
            ->groupBy(fn ($q) => $q->type ?: 'unknown')
            ->map->count()
            ->sortKeys();

        $marksDistribution = $questions
            ->groupBy(fn ($q) => (string) ($q->pivot->marks_override ?? $q->marks))
            ->map->count()
            ->sortKeysUsing(fn ($a, $b) => (int) $a <=> (int) $b);

        $formatLabels = ExamFormOptions::formatLabels();
        $formats = is_array($exam->exam_format) ? $exam->exam_format : [];

        return view('backend.exams.show', compact(
            'exam',
            'stats',
            'difficultyDistribution',
            'typeDistribution',
            'marksDistribution',
            'formatLabels',
            'formats'
        ));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit($id): View
    {
        $exam = $this->findExamOrFail((int) $id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');

        $orgId = $this->currentOrgId();
        $categories = app(\App\Services\ExamCategoryService::class)->getHierarchicalList($orgId);
        $formOptions = ExamFormOptions::all($orgId);

        // The edit form only needs the currently linked question ids for
        // hydration (window.examFormConfig); the full question bank is loaded
        // on demand client-side, so there is no need for the 500-row list.
        $exam->load('questions:id');

        return view('backend.exams.edit', compact('exam', 'categories', 'formOptions'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(UpdateExamRequest $request, $id): RedirectResponse
    {
        $exam = Exam::findOrFail($id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        $this->examService->update($exam, $request->validated());

        return redirect()
            ->route('admin.exams.show', $exam)
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Start or resume an attempt with stable assigned questions (no correct answers).
     */
    public function startAttempt(Request $request, $exam): \Illuminate\Http\JsonResponse
    {
        $examModel = $this->findExamOrFail((int) $exam);
        abort_if($examModel->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');

        try {
            $attempt = $this->examAttemptService->start($examModel, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Unable to start attempt.',
                'errors' => $e->errors(),
            ], 422);
        } catch (AttemptQuestionShortageException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'shortages' => $e->report(),
            ], 422);
        }

        return response()->json(
            $this->examAttemptService->toCandidateStartPayload($attempt)
        );
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy($id): RedirectResponse
    {
        $exam = Exam::findOrFail($id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        $this->examService->delete($exam);

        return redirect()
            ->route('admin.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $exam = Exam::withTrashed()->forOrg($this->currentOrgId())->findOrFail($id);
        abort_unless($exam->trashed(), 404);
        $exam->restore();

        return redirect()->route('admin.exams.index', ['tab' => 'bin'])
            ->with('success', 'Exam restored successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $count = Exam::forOrg($this->currentOrgId())->whereIn('id', $ids)->get()
            ->each->delete()->count();

        return redirect()->route('admin.exams.index')
            ->with('success', "{$count} exam(s) moved to bin.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $count = Exam::onlyTrashed()->forOrg($this->currentOrgId())->whereIn('id', $ids)->restore();

        return redirect()->route('admin.exams.index', ['tab' => 'bin'])
            ->with('success', "{$count} exam(s) restored.");
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(['draft', 'published', 'active', 'inactive', 'suspended'])],
        ]);
        $count = Exam::forOrg($this->currentOrgId())
            ->whereIn('id', array_unique($validated['ids']))
            ->update(['status' => $validated['status']]);

        return redirect()->route('admin.exams.index')
            ->with('success', "Status updated for {$count} exam(s).");
    }

    /** @return list<int> */
    private function validatedIds(Request $request): array
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $validated['ids'])));
    }

    // ── Publish ───────────────────────────────────────────────────────────────

    public function publish($id): RedirectResponse
    {
        $exam = Exam::findOrFail($id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');
        $this->examService->publish($exam);

        return redirect()
            ->route('admin.exams.show', $exam)
            ->with('success', 'Exam published successfully.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function findExamOrFail(int $id): Exam
    {
        return Exam::query()
            ->with(['questions', 'category', 'createdBy', 'ogImage'])
            ->findOrFail($id);
    }

    /**
     * Get Question categories hierarchical tree for the organization.
     */
    public function apiCategories(): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();

        $categories = \App\Models\QuestionCategory::where('organization_id', $orgId)
            ->where('status', 'active')
            ->get();

        $questionCounts = \App\Models\Question::where('organization_id', $orgId)
            ->where('status', 'active')
            ->groupBy('category_id')
            ->selectRaw('category_id, count(*) as count')
            ->pluck('count', 'category_id')
            ->toArray();

        $grouped = $categories->groupBy('parent_id');

        $buildTree = function ($parentId) use ($grouped, $questionCounts, &$buildTree) {
            $tree = [];
            $items = $grouped->get($parentId, collect([]))->sortBy('name');

            foreach ($items as $item) {
                $children = $buildTree($item->id);

                // Calculate availableQuestions: count in this category + sum of children
                $available = $questionCounts[$item->id] ?? 0;
                foreach ($children as $child) {
                    $available += $child['availableQuestions'];
                }

                $tree[] = [
                    'id'                 => (string) $item->id,
                    'name'               => $item->name,
                    'availableQuestions' => $available,
                    'children'           => $children,
                ];
            }
            return $tree;
        };

        $tree = $buildTree(null);

        return response()->json($tree);
    }

    /**
     * Per-category matching counts for question-bank accordion headers.
     */
    public function apiQuestionCounts(Request $request): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();
        $filters = $this->questionBankFiltersFromRequest($request);
        $bucketIds = $filters['categories'] ?? [];
        if (is_string($bucketIds)) {
            $bucketIds = array_filter(explode(',', $bucketIds));
        }
        if (! is_array($bucketIds)) {
            $bucketIds = [];
        }

        return response()->json(
            $this->questionBankService->countsByCategory($orgId, $filters, $bucketIds)
        );
    }

    /**
     * Cursor-paginated question bank for exam create/edit.
     */
    public function apiQuestions(Request $request): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();
        $filters = $this->questionBankFiltersFromRequest($request);
        $cursor = $request->filled('cursor') ? (int) $request->query('cursor') : null;
        $perPage = (int) $request->query('per_page', QuestionBankService::DEFAULT_PAGE_SIZE);

        return response()->json(
            $this->questionBankService->paginate($orgId, $filters, $cursor, $perPage)
        );
    }

    /**
     * Server-side random sample respecting filters and optional category quotas.
     */
    public function apiRandomQuestions(Request $request): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();
        $filters = $this->questionBankFiltersFromRequest($request);
        $count = max(0, (int) $request->input('count', $request->query('count', 0)));
        $quotas = $request->input('category_quotas', $request->query('category_quotas', []));
        if (is_string($quotas)) {
            $decoded = json_decode($quotas, true);
            $quotas = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($quotas)) {
            $quotas = [];
        }

        $sample = $this->questionBankService->randomSample($orgId, $filters, $count, $quotas);

        return response()->json([
            'data' => $sample,
            'meta' => [
                'requested' => $count,
                'returned' => count($sample),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionBankFiltersFromRequest(Request $request): array
    {
        return [
            'categories' => $request->input('categories', $request->query('categories')),
            'marks' => $request->input('marks', $request->query('marks')),
            'formats' => $request->input('formats', $request->query('formats')),
            'difficulty' => $request->input('difficulty', $request->query('difficulty')),
            'types' => $request->input('types', $request->query('types')),
            'exclude_ids' => $request->input('exclude_ids', $request->query('exclude_ids')),
            'ids' => $request->input('ids', $request->query('ids')),
            'q' => $request->input('q', $request->query('q')),
        ];
    }
}
