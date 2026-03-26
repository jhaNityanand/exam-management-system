<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Concerns\InteractsWithOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreQuestionRequest;
use App\Http\Requests\Editor\UpdateQuestionRequest;
use App\Models\Question;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use InteractsWithOrganization;

    public function __construct(protected QuestionService $questionService)
    {
    }

    public function index(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('question.view'), 403);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());

        return view('editor.questions.index', compact('categories'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('question.create'), 403);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());

        return view('editor.questions.create', compact('categories'));
    }

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $this->currentOrgId();
        $data['allows_multiple'] = $request->boolean('allows_multiple');

        if ($request->has('options') && is_array($data['options'] ?? null)) {
            $wrapped = [];
            foreach ($data['options'] as $i => $row) {
                $wrapped[] = is_array($row) ? $row : ['text' => (string) $row];
            }
            $data['options'] = $this->questionService->normalizeOptionsFromRequest($wrapped, $request);
        }

        $this->questionService->create($data);

        return redirect()->route('editor.questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function show(Question $question): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('question.view'), 403);
        $this->authorizeOrgModel($question);

        return view('editor.questions.show', compact('question'));
    }

    public function edit(Question $question): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('question.update'), 403);
        $this->authorizeOrgModel($question);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());

        return view('editor.questions.edit', compact('question', 'categories'));
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->authorizeOrgModel($question);
        $data = $request->validated();
        $data['allows_multiple'] = $request->boolean('allows_multiple');

        if ($request->has('options') && is_array($data['options'] ?? null)) {
            $wrapped = [];
            foreach ($data['options'] as $i => $row) {
                $wrapped[] = is_array($row) ? $row : ['text' => (string) $row];
            }
            $data['options'] = $this->questionService->normalizeOptionsFromRequest($wrapped, $request);
        }

        $this->questionService->update($question, $data);

        return redirect()->route('editor.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('question.delete'), 403);
        $this->authorizeOrgModel($question);
        $this->questionService->delete($question);

        return redirect()->route('editor.questions.index')
            ->with('success', 'Question deleted successfully.');
    }
}
