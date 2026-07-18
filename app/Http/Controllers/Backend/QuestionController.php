<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Question\StoreQuestionRequest;
use App\Http\Requests\Backend\Question\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Services\QuestionCategoryService;
use App\Services\QuestionService;
use App\Support\ExamFormats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected QuestionService $service,
        protected QuestionCategoryService $categoryService
    ) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();
        $categories = $this->categoryService->getHierarchicalList($orgId);

        return view('backend.questions.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        $orgId = $this->currentOrgId();
        $categories = $this->categoryService->getHierarchicalList($orgId);
        $questionTypes = ExamFormats::questionTypes();
        $defaults = $this->resolveCreateDefaults($request, $orgId);
        $examCreateReturn = $defaults['source'] === 'exam-create'
            ? route('admin.exams.create')
            : null;
        $createdFromExam = session()->pull('exam_create_question_created');

        return view('backend.questions.create', compact(
            'categories',
            'questionTypes',
            'defaults',
            'examCreateReturn',
            'createdFromExam'
        ));
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data = $request->validated();
        $data['organization_id'] = $orgId;

        $question = $this->service->create($data);
        $source = (string) $request->input('source', '');

        if ($source === 'exam-create') {
            return redirect()
                ->route('admin.questions.create', ['source' => 'exam-create'])
                ->with('success', 'Question created successfully.')
                ->with('exam_create_question_created', [
                    'id' => $question->id,
                    'categoryId' => (string) $question->category_id,
                    'marks' => (int) $question->marks,
                    'difficulty' => $question->difficulty,
                    'type' => $question->type,
                    'allowsMultiple' => (bool) $question->allows_multiple,
                    'text' => strip_tags((string) $question->body),
                ]);
        }

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function show($id): View
    {
        $question = Question::with(['category', 'createdBy'])->findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        return view('backend.questions.show', compact('question'));
    }

    public function edit($id): View
    {
        $question = Question::findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        $categories = $this->categoryService->getHierarchicalList($orgId);
        $questionTypes = ExamFormats::questionTypes();

        return view('backend.questions.edit', compact('question', 'categories', 'questionTypes'));
    }

    public function update(UpdateQuestionRequest $request, $id): RedirectResponse
    {
        $question = Question::findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        $data = $request->validated();
        $this->service->update($question, $data);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $question = Question::findOrFail($id);
        $orgId = $this->currentOrgId();
        abort_if($question->organization_id !== $orgId, 403, 'Unauthorized access to this question.');

        $this->service->delete($question);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $question = Question::withTrashed()->forOrg($this->currentOrgId())->findOrFail($id);
        abort_unless($question->trashed(), 404);
        $question->restore();

        return redirect()->route('admin.questions.index', ['tab' => 'bin'])
            ->with('success', 'Question restored successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $count = Question::forOrg($this->currentOrgId())->whereIn('id', $ids)->get()
            ->each->delete()->count();

        return redirect()->route('admin.questions.index')
            ->with('success', "{$count} question(s) moved to bin.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $count = Question::onlyTrashed()->forOrg($this->currentOrgId())->whereIn('id', $ids)->restore();

        return redirect()->route('admin.questions.index', ['tab' => 'bin'])
            ->with('success', "{$count} question(s) restored.");
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);
        $count = Question::forOrg($this->currentOrgId())
            ->whereIn('id', array_unique($validated['ids']))
            ->update(['status' => $validated['status']]);

        return redirect()->route('admin.questions.index')
            ->with('success', "Status updated for {$count} question(s).");
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

    /**
     * @return array{
     *     source: string|null,
     *     category_id: int|null,
     *     type: string|null,
     *     allows_multiple: bool|null,
     *     difficulty: string|null,
     *     marks_type: string,
     *     marks: int|null,
     *     marks_list: list<int>,
     *     formats: list<string>
     * }
     */
    private function resolveCreateDefaults(Request $request, int $orgId): array
    {
        $source = $request->query('source') === 'exam-create' ? 'exam-create' : null;

        $categoryId = $request->integer('category_id') ?: null;
        if ($categoryId) {
            $exists = QuestionCategory::query()
                ->where('organization_id', $orgId)
                ->where('status', 'active')
                ->whereKey($categoryId)
                ->exists();
            if (! $exists) {
                $categoryId = null;
            }
        }

        $marksInput = $request->query('marks', []);
        if (! is_array($marksInput)) {
            $marksInput = [$marksInput];
        }
        $marksList = array_values(array_unique(array_filter(array_map(
            static fn ($value) => (int) $value,
            $marksInput
        ), static fn (int $value) => $value >= 1 && $value <= 10)));

        $formatsInput = $request->query('formats', []);
        if (! is_array($formatsInput)) {
            $formatsInput = [$formatsInput];
        }
        $formats = array_values(array_unique(array_filter(
            array_map('strval', $formatsInput),
            static fn (string $format) => in_array($format, ExamFormats::ids(), true)
        )));

        $type = null;
        $allowsMultiple = null;
        $constraints = ExamFormats::questionConstraints();
        if (count($formats) === 1) {
            $rules = $constraints[$formats[0]] ?? [];
            if (count($rules) === 1) {
                $type = $rules[0]['type'] ?? null;
                $allowsMultiple = array_key_exists('allows_multiple', $rules[0])
                    ? $rules[0]['allows_multiple']
                    : null;
            }
        }

        $requestedType = (string) $request->query('type', '');
        if (in_array($requestedType, ExamFormats::questionTypeIds(), true)) {
            $type = $requestedType;
        }

        $difficulty = (string) $request->query('difficulty', '');
        if (! in_array($difficulty, ['easy', 'medium', 'hard', 'very_hard'], true)) {
            $difficulty = null;
        }

        return [
            'source' => $source,
            'category_id' => $categoryId,
            'type' => $type,
            'allows_multiple' => is_bool($allowsMultiple) ? $allowsMultiple : null,
            'difficulty' => $difficulty,
            'marks_type' => count($marksList) > 1 ? 'multiple' : 'single',
            'marks' => $marksList[0] ?? null,
            'marks_list' => $marksList,
            'formats' => $formats,
        ];
    }
}
