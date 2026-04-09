<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrgAdmin\StoreExamRequest;
use App\Http\Requests\OrgAdmin\UpdateExamRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Services\ExamService;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(
        protected ExamService $examService,
        protected QuestionService $questionService
    ) {
    }

    protected function currentOrgId(): int
    {
        $id = current_organization_id();
        abort_if($id === null, 404, 'No organization context.');
        return $id;
    }

    public function index(): View
    {
        return view('workspace.exams.index');
    }

    public function create(): View
    {
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());
        $questions = Question::forOrg($this->currentOrgId())->orderBy('body')->limit(500)->get(['id', 'body', 'category_id']);

        return view('workspace.exams.create', compact('categories', 'questions'));
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $this->currentOrgId();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $this->examService->create($data);

        return redirect()->route('workspace.exams.index')
            ->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): View
    {
        abort_if((int) $exam->organization_id !== $this->currentOrgId(), 403);
        $exam->load(['questions', 'category']);
        $stats = $this->examService->getAttemptStats($exam);

        return view('workspace.exams.show', compact('exam', 'stats'));
    }

    public function edit(Exam $exam): View
    {
        abort_if((int) $exam->organization_id !== $this->currentOrgId(), 403);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());
        $questions = Question::forOrg($this->currentOrgId())->orderBy('body')->limit(500)->get(['id', 'body', 'category_id']);
        $exam->load('questions');

        return view('workspace.exams.edit', compact('exam', 'categories', 'questions'));
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        abort_if((int) $exam->organization_id !== $this->currentOrgId(), 403);
        $data = $request->validated();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $this->examService->update($exam, $data);

        return redirect()->route('workspace.exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        abort_if((int) $exam->organization_id !== $this->currentOrgId(), 403);
        $this->examService->delete($exam);

        return redirect()->route('workspace.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        abort_if((int) $exam->organization_id !== $this->currentOrgId(), 403);
        $this->examService->publish($exam);

        return redirect()->route('workspace.exams.show', $exam)
            ->with('success', 'Exam published successfully.');
    }
}
