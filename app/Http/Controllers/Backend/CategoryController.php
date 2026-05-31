<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreCategoryRequest;
use App\Http\Requests\Workspace\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService) {}

    /**
     * Resolve the active organization ID.
     *
     * SINGLE-ORG MODE: current_organization_id() always returns the one org.
     * MULTI-ORG MODE (future): restore the abort_if checks in edit/update/destroy
     *   to enforce org ownership — abort_if((int) $category->organization_id !== $this->currentOrgId(), 403).
     */
    protected function currentOrgId(): int
    {
        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }

    public function index(): View
    {
        return view('backend.categories.index');
    }

    public function create(): View
    {
        $parents = $this->categoryService->listForSelect($this->currentOrgId());

        return view('backend.categories.create', compact('parents'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $this->currentOrgId();
        $this->categoryService->create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        // MULTI-ORG: abort_if((int) $category->organization_id !== $this->currentOrgId(), 403);
        $parents = $this->categoryService->listForSelect($this->currentOrgId())
            ->where('id', '!=', $category->id);

        return view('backend.categories.edit', compact('category', 'parents'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        // MULTI-ORG: abort_if((int) $category->organization_id !== $this->currentOrgId(), 403);
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // MULTI-ORG: abort_if((int) $category->organization_id !== $this->currentOrgId(), 403);
        $this->categoryService->delete($category);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category removed.');
    }
}
