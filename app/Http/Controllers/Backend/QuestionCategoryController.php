<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Concerns\HandlesCategoryListActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\QuestionCategory\StoreQuestionCategoryRequest;
use App\Http\Requests\Backend\QuestionCategory\UpdateQuestionCategoryRequest;
use App\Models\QuestionCategory;
use App\Services\QuestionCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * QuestionCategoryController
 *
 * Handles CRUD for Question Categories. Categories are always scoped to
 * the authenticated user's organization (resolved via user_organizations).
 *
 * Route prefix : admin/questions/categories
 * Route names  : admin.questions.categories.{index|create|store|edit|update|destroy}
 */
class QuestionCategoryController extends Controller
{
    use HandlesCategoryListActions, ResolvesCurrentOrganization;

    protected function categoryModelClass(): string { return QuestionCategory::class; }
    protected function categoryIndexRoute(): string { return 'admin.questions.categories.index'; }

    public function __construct(
        protected QuestionCategoryService $service
    ) {}

    // ── CRUD actions ──────────────────────────────────────────────────────────

    /**
     * List all root categories with their children (tree view).
     */
    public function index(Request $request)
    {
        $orgId  = $this->currentOrgId();
        $search = trim($request->query('search', ''));
        $status = $request->query('status', '');
        $sort   = $request->query('sort', 'name_asc');
        $trash  = $request->query('trash', 'active');

        if ($request->ajax()) {
            // Retrieve all categories for organization with DB ordering
            $query = QuestionCategory::forOrg($orgId);
            if ($trash === 'bin') {
                $query->onlyTrashed();
            }

            [$col, $dir] = match ($sort) {
                'name_desc' => ['name', 'desc'],
                'newest'    => ['created_at', 'desc'],
                'oldest'    => ['created_at', 'asc'],
                default     => ['name', 'asc'],
            };

            $allCategories = $query->orderBy($col, $dir)->get();

            // Perform in-memory tree building & filtering
            $matchedIds = [];
            foreach ($allCategories as $cat) {
                $statusMatches = empty($status) || $cat->status === $status;
                $searchMatches = empty($search) ||
                    (\Illuminate\Support\Str::contains(strtolower($cat->name), strtolower($search)) ||
                     \Illuminate\Support\Str::contains(strtolower($cat->description ?? ''), strtolower($search)));

                if ($statusMatches && $searchMatches) {
                    $matchedIds[$cat->id] = true;
                }
            }

            $keptIds = [];
            $catMap = [];
            foreach ($allCategories as $cat) {
                $catMap[$cat->id] = $cat;
            }

            foreach ($matchedIds as $id => $true) {
                $curr = $catMap[$id] ?? null;
                while ($curr) {
                    $keptIds[$curr->id] = true;
                    $curr = $curr->parent_id ? ($catMap[$curr->parent_id] ?? null) : null;
                }
            }

            $roots = [];
            $childrenMap = [];
            foreach ($allCategories as $cat) {
                if (!isset($keptIds[$cat->id])) {
                    continue;
                }
                $cat->setRelation('children', collect([]));
                if (empty($cat->parent_id)) {
                    $roots[] = $cat;
                } else {
                    $childrenMap[$cat->parent_id][] = $cat;
                }
            }

            foreach ($allCategories as $cat) {
                if (isset($childrenMap[$cat->id])) {
                    $cat->setRelation('children', collect($childrenMap[$cat->id]));
                }
            }

            $categories = collect($roots);

            return view('backend.question-categories.partials.tree-list', compact('categories'))->render();
        }

        // Return initial view shell (non-ajax load - no categories to fetch yet)
        $categories = collect([]);
        return view('backend.question-categories.index', compact(
            'categories',
            'search',
            'status',
            'sort',
        ));
    }

    /**
     * Display the specified category details in JSON for the View modal.
     */
    public function show(QuestionCategory $category): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $category->load([
            'parent',
            'children' => fn ($q) => $q->orderBy('name'),
        ]);

        // Formatted dates for presentation
        $category->formatted_created_at = $category->created_at ? $category->created_at->format('M d, Y h:i A') : 'N/A';
        $category->formatted_updated_at = $category->updated_at ? $category->updated_at->format('M d, Y h:i A') : 'N/A';

        return response()->json($category);
    }


    /**
     * Show the category tree-builder form.
     */
    public function create(): View
    {
        return view('backend.question-categories.create');
    }

    /**
     * Persist the category tree created by the builder form.
     */
    public function store(StoreQuestionCategoryRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data  = $request->validated();

        // Decode parent relationship map from the hidden JSON field
        $parentMapRaw = $request->input('_parent_map', '{}');
        $parentMap    = json_decode($parentMapRaw, true) ?: [];

        // Shared metadata / flags applied to all nodes in the tree
        $meta = array_filter([
            'status'          => $data['status']          ?? 'active',
            'meta_title'      => $data['meta_title']      ?? null,
            'meta_description'=> $data['meta_description'] ?? null,
            'meta_keywords'   => $data['meta_keywords']   ?? null,
            'slug'            => $data['slug']            ?? null,
            'canonical_url'   => $data['canonical_url']   ?? null,
            'og_title'        => $data['og_title']        ?? null,
            'og_description'  => $data['og_description']  ?? null,
            'ai_generated'    => (bool) ($data['ai_generated'] ?? false),
            'ai_improve'      => (bool) ($data['ai_improve']   ?? false),
        ], fn ($v) => $v !== null);

        $this->service->createTree($orgId, $data['categories'], $parentMap, $meta);

        return redirect()
            ->route('admin.questions.categories.index')
            ->with('success', 'Category hierarchy saved successfully.');
    }

    /**
     * Show the edit form for a single category.
     * Loads the category and its children recursively for the tree canvas.
     */
    public function edit(QuestionCategory $category): View
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $category->load([
            'childrenRecursive' => fn ($q) => $q->orderBy('name'),
        ]);

        return view('backend.question-categories.edit', compact('category'));
    }

    /**
     * Persist category edits (saves the entire hierarchy).
     */
    public function update(
        UpdateQuestionCategoryRequest $request,
        QuestionCategory              $category,
    ): RedirectResponse {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $data = $request->validated();

        // Decode parent relationship map from the hidden JSON field
        $parentMapRaw = $request->input('_parent_map', '{}');
        $parentMap    = json_decode($parentMapRaw, true) ?: [];

        // Shared metadata / flags applied to all nodes in the tree
        $meta = [
            'status'          => $data['status']          ?? 'active',
            'meta_title'      => $data['meta_title']      ?? null,
            'meta_description'=> $data['meta_description'] ?? null,
            'meta_keywords'   => $data['meta_keywords']   ?? null,
            'slug'            => $data['slug']            ?? null,
            'canonical_url'   => $data['canonical_url']   ?? null,
            'og_title'        => $data['og_title']        ?? null,
            'og_description'  => $data['og_description']  ?? null,
            'ai_generated'    => (bool) ($data['ai_generated'] ?? false),
            'ai_improve'      => (bool) ($data['ai_improve']   ?? false),
        ];

        $this->service->updateTree($category, $data['categories'], $parentMap, $meta);

        return redirect()
            ->route('admin.questions.categories.index')
            ->with('success', 'Category hierarchy updated successfully.');
    }

    /**
     * Soft-delete a category (children and questions cascade via model hook).
     */
    public function destroy(QuestionCategory $category): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $this->service->delete($category);

        return redirect()
            ->route('admin.questions.categories.index')
            ->with('success', "Category \"{$category->name}\" deleted successfully.");
    }
}
