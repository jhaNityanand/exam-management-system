<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Exam\StoreExamRequest;
use App\Http\Requests\Backend\Exam\UpdateExamRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Models\UserOrganization;
use App\Services\ExamService;
use App\Support\ExamFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    // ── Org helper ────────────────────────────────────────────────────────────

    protected function currentOrgId(): int
    {
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            if ($orgId) {
                return (int) $orgId;
            }
        }

        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }

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
        $formOptions = ExamFormOptions::all();

        $questions = Question::query()
            ->orderBy('body')
            ->limit(500)
            ->get(['id', 'body', 'category_id', 'marks', 'difficulty', 'type']);

        return view('backend.exams.create', compact('categories', 'questions', 'formOptions'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Attach current organisation
        $data['organization_id'] = current_organization_id();

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

        return view('backend.exams.show', compact('exam', 'stats'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit($id): View
    {
        $exam = $this->findExamOrFail((int) $id);
        abort_if($exam->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this exam.');

        $orgId = $this->currentOrgId();
        $categories = app(\App\Services\ExamCategoryService::class)->getHierarchicalList($orgId);
        $formOptions = ExamFormOptions::all();

        $questions = Question::query()
            ->orderBy('body')
            ->limit(500)
            ->get(['id', 'body', 'category_id', 'marks', 'difficulty', 'type']);

        return view('backend.exams.edit', compact('exam', 'categories', 'questions', 'formOptions'));
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
            ->with(['questions', 'category', 'createdBy'])
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
     * Get dynamic list of filtered questions.
     */
    public function apiQuestions(Request $request): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();

        $query = \App\Models\Question::where('organization_id', $orgId)
            ->where('status', 'active');

        // Filter by categories (include descendants recursively)
        $categoriesParam = $request->query('categories');
        if ($categoriesParam) {
            $categoryIds = array_filter(explode(',', $categoriesParam));
            if (!empty($categoryIds)) {
                $descendantIds = $this->getDescendantCategoryIds($categoryIds);
                $query->whereIn('category_id', $descendantIds);
            }
        }

        // Filter by marks
        $marksParam = $request->query('marks');
        if ($marksParam) {
            $marksList = array_filter(explode(',', $marksParam));
            if (!empty($marksList)) {
                $query->whereIn('marks', $marksList);
            }
        }

        // Filter by formats/types
        $formatsParam = $request->query('formats');
        if ($formatsParam) {
            $formatList = array_filter(explode(',', $formatsParam));
            if (!empty($formatList)) {
                $query->where(function ($q) use ($formatList) {
                    foreach ($formatList as $format) {
                        if ($format === 'mcq') {
                            $q->orWhere(function ($sub) {
                                $sub->where('type', 'mcq')->where('allows_multiple', false);
                            });
                        } elseif ($format === 'multi_select') {
                            $q->orWhere(function ($sub) {
                                $sub->where('type', 'mcq')->where('allows_multiple', true);
                            });
                        } elseif ($format === 'written') {
                            $q->orWhere('type', 'short_answer');
                        }
                    }
                });
            }
        }

        $questions = $query->get(['id', 'category_id', 'marks', 'difficulty', 'body']);

        $formatted = $questions->map(function ($q) {
            return [
                'id'         => $q->id,
                'categoryId' => (string) $q->category_id,
                'marks'      => $q->marks,
                'difficulty' => $q->difficulty,
                'text'       => strip_tags($q->body),
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Resolve descendant category IDs recursively.
     */
    protected function getDescendantCategoryIds(array $categoryIds): array
    {
        $allIds = array_map('intval', $categoryIds);
        $toProcess = $allIds;

        while (!empty($toProcess)) {
            $childrenIds = \App\Models\QuestionCategory::whereIn('parent_id', $toProcess)
                ->pluck('id')
                ->toArray();

            $newChildren = array_diff($childrenIds, $allIds);
            if (empty($newChildren)) {
                break;
            }
            $allIds = array_merge($allIds, $newChildren);
            $toProcess = $newChildren;
        }

        return $allIds;
    }
}
