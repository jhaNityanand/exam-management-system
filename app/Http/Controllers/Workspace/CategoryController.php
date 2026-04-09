<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreCategoryRequest;
use App\Http\Requests\Workspace\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService)
    {
    }

    protected function currentOrgId(): int
    {
        $id = current_organization_id();
        abort_if($id === null, 404, 'No organization context. Please select an organization.');
        return $id;
    }

    public function index(): View
    {
        return view('workspace.categories.index');
    }

    public function tree(): View
    {
        $tree = $this->categoryService->treeForOrg($this->currentOrgId(), true);

        return view('workspace.categories.tree', ['tree' => $tree]);
    }

    public function create(): View
    {
        $parents = $this->categoryService->listForSelect($this->currentOrgId());

        return view('workspace.categories.create', ['parents' => $parents]);
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
        abort_if((int) $category->organization_id !== $this->currentOrgId(), 403);
        $parents = $this->categoryService->listForSelect($this->currentOrgId())
            ->where('id', '!=', $category->id);

        return view('workspace.categories.edit', [
            'category' => $category,
            'parents'  => $parents,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        abort_if((int) $category->organization_id !== $this->currentOrgId(), 403);
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('workspace.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if((int) $category->organization_id !== $this->currentOrgId(), 403);
        $this->categoryService->delete($category);

        return redirect()->route('workspace.categories.index')
            ->with('success', 'Category removed.');
    }
}
