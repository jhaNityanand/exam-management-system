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
}
