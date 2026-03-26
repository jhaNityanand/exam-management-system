<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Concerns\InteractsWithOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreCategoryRequest;
use App\Http\Requests\Workspace\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use InteractsWithOrganization;

    public function __construct(protected CategoryService $categoryService)
    {
    }

    public function index(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('category.view'), 403);

        return view('workspace.categories.index', ['panelLayout' => $this->panelLayout()]);
    }

    public function tree(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('category.view'), 403);
        $tree = $this->categoryService->treeForOrg($this->currentOrgId(), true);

        return view('workspace.categories.tree', [
            'tree' => $tree,
            'panelLayout' => $this->panelLayout(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('category.create'), 403);
        $parents = $this->categoryService->listForSelect($this->currentOrgId());

        return view('workspace.categories.create', [
            'parents' => $parents,
            'panelLayout' => $this->panelLayout(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $this->currentOrgId();
        $this->categoryService->create($data);

        return redirect()->route('workspace.categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        abort_unless(auth()->user()?->canInCurrentOrg('category.update'), 403);
        $this->authorizeOrgModel($category);
        $parents = $this->categoryService->listForSelect($this->currentOrgId())
            ->where('id', '!=', $category->id);

        return view('workspace.categories.edit', [
            'category' => $category,
            'parents' => $parents,
            'panelLayout' => $this->panelLayout(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorizeOrgModel($category);
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('workspace.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_unless(auth()->user()?->canInCurrentOrg('category.delete'), 403);
        $this->authorizeOrgModel($category);
        $this->categoryService->delete($category);

        return redirect()->route('workspace.categories.index')
            ->with('success', 'Category removed.');
    }
}
