<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Question\StoreQuestionRequest;
use App\Http\Requests\Backend\Question\UpdateQuestionRequest;
use App\Models\Question;
use App\Services\QuestionCategoryService;
use App\Services\QuestionService;
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

    public function create(): View
    {
        $orgId = $this->currentOrgId();
        $categories = $this->categoryService->getHierarchicalList($orgId);
        $questionTypes = \App\Support\ExamFormats::questionTypes();

        return view('backend.questions.create', compact('categories', 'questionTypes'));
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data = $request->validated();
        $data['organization_id'] = $orgId;

        $this->service->create($data);

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
        $questionTypes = \App\Support\ExamFormats::questionTypes();

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
}
