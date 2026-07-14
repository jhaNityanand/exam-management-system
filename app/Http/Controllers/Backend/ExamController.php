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
        $formOptions = ExamFormOptions::all($orgId);

        return view('backend.exams.create', compact('categories', 'formOptions'));
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

        // Filter by formats → question types
        $formatsParam = $request->query('formats');
        if ($formatsParam) {
            $formatList = array_values(array_filter(explode(',', $formatsParam)));
            $constraints = \App\Support\ExamFormOptions::examFormatQuestionConstraints();

            if ($formatList !== []) {
                $query->where(function ($q) use ($formatList, $constraints) {
                    foreach ($formatList as $format) {
                        $rules = $constraints[$format] ?? [];
                        foreach ($rules as $rule) {
                            $q->orWhere(function ($sub) use ($rule) {
                                $sub->where('type', $rule['type']);
                                if ($rule['allows_multiple'] !== null) {
                                    $sub->where('allows_multiple', $rule['allows_multiple']);
                                }
                            });
                        }
                    }
                });
            }
        }

        // Filter by difficulty
        $difficultyParam = $request->query('difficulty');
        if ($difficultyParam) {
            $difficulties = array_values(array_filter(explode(',', $difficultyParam)));
            if ($difficulties !== []) {
                $query->whereIn('difficulty', $difficulties);
            }
        }

        // Filter by question type (direct, optional)
        $typesParam = $request->query('types');
        if ($typesParam) {
            $types = array_values(array_filter(explode(',', $typesParam)));
            if ($types !== []) {
                $query->whereIn('type', $types);
            }
        }

        $questions = $query
            ->with('category:id,name,parent_id')
            ->orderBy('id')
            ->limit(2000)
            ->get(['id', 'category_id', 'marks', 'difficulty', 'type', 'allows_multiple', 'body']);

        $formatted = $questions->map(function ($q) {
            return [
                'id'              => $q->id,
                'categoryId'      => (string) $q->category_id,
                'marks'           => $q->marks,
                'difficulty'      => $q->difficulty,
                'type'            => $q->type,
                'allowsMultiple'  => (bool) $q->allows_multiple,
                'text'            => strip_tags($q->body),
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
