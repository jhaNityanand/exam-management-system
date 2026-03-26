<?php

namespace App\Http\Controllers\OrgAdmin;

use App\Http\Controllers\Concerns\InteractsWithOrganization;
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
    use InteractsWithOrganization;

    public function __construct(
        protected ExamService $examService,
        protected QuestionService $questionService
    ) {
    }

    public function index(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.view'), 403);

        return view('org-admin.exams.index');
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.create'), 403);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());
        $questions = Question::forOrg($this->currentOrgId())->orderBy('body')->limit(500)->get(['id', 'body', 'category_id']);

        return view('org-admin.exams.create', compact('categories', 'questions'));
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $this->currentOrgId();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $this->examService->create($data);

        return redirect()->route('org-admin.exams.index')
            ->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.view'), 403);
        $this->authorizeOrgModel($exam);
        $exam->load(['questions', 'category']);
        $stats = $this->examService->getAttemptStats($exam);

        return view('org-admin.exams.show', compact('exam', 'stats'));
    }

    public function edit(Exam $exam): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.update'), 403);
        $this->authorizeOrgModel($exam);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());
        $questions = Question::forOrg($this->currentOrgId())->orderBy('body')->limit(500)->get(['id', 'body', 'category_id']);
        $exam->load('questions');

        return view('org-admin.exams.edit', compact('exam', 'categories', 'questions'));
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorizeOrgModel($exam);
        $data = $request->validated();
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $this->examService->update($exam, $data);

        return redirect()->route('org-admin.exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.delete'), 403);
        $this->authorizeOrgModel($exam);
        $this->examService->delete($exam);

        return redirect()->route('org-admin.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('exam.publish'), 403);
        $this->authorizeOrgModel($exam);
        $this->examService->publish($exam);

        return redirect()->route('org-admin.exams.show', $exam)
            ->with('success', 'Exam published successfully.');
    }
}
