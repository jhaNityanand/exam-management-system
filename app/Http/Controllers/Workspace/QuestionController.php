<?php

namespace App\Http\Controllers\Workspace;

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
    public function __construct(protected QuestionService $questionService)
    {
    }

    protected function currentOrgId(): int
    {
        $id = current_organization_id();
        abort_if($id === null, 404, 'No organization context.');
        return $id;
    }

    public function index(): View
    {
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());

        return view('workspace.questions.index', compact('categories'));
    }

    public function create(): View
    {
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());

        return view('workspace.questions.create', compact('categories'));
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

        return redirect()->route('workspace.questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function show(Question $question): View
    {
        abort_if((int) $question->organization_id !== $this->currentOrgId(), 403);

        return view('workspace.questions.show', compact('question'));
    }

    public function edit(Question $question): View
    {
        abort_if((int) $question->organization_id !== $this->currentOrgId(), 403);
        $categories = $this->questionService->getCategoriesForOrg($this->currentOrgId());

        return view('workspace.questions.edit', compact('question', 'categories'));
    }

    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        abort_if((int) $question->organization_id !== $this->currentOrgId(), 403);
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

        return redirect()->route('workspace.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        abort_if((int) $question->organization_id !== $this->currentOrgId(), 403);
        $this->questionService->delete($question);

        return redirect()->route('workspace.questions.index')
            ->with('success', 'Question deleted successfully.');
    }
}
