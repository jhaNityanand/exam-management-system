<?php

namespace App\Http\Controllers\Backend\QuestionCategory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\QuestionCategory\StoreQuestionCategoryRequest;
use App\Http\Requests\Backend\QuestionCategory\UpdateQuestionCategoryRequest;
use App\Models\QuestionCategory;
use App\Models\UserOrganization;
use App\Services\QuestionCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function __construct(
        protected QuestionCategoryService $service
    ) {}

    // ── Organization helper ───────────────────────────────────────────────────

    /**
     * Resolve the authenticated user's active organization ID.
     *
     * Priority:
     *   1. First active user_organizations record for the current user.
     *   2. Fallback to global helper (first org — for CLI / seeders).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  503 when no org found.
     */
    protected function currentOrgId(): int
    {
        // Look up via user_organizations first
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            if ($orgId) {
                return (int) $orgId;
            }
        }

        // Global fallback (single-org mode / CLI)
        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }

    // ── CRUD actions ──────────────────────────────────────────────────────────

    /**
     * List all root categories with their children (tree view).
     */
    public function index(Request $request): View
    {
        $orgId  = $this->currentOrgId();
        $search = trim($request->query('search', ''));
        $status = $request->query('status', '');
        $sort   = $request->query('sort', 'name_asc');

        $categories = $this->service->treeForOrg($orgId);

        return view('backend.question-categories.index', compact(
            'categories',
            'search',
            'status',
            'sort',
        ));
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
     *
     * Payload shape:
     *   categories[node-N][name], categories[node-N][description]
     *   _parent_map  (JSON): {"node-1":"node-0", "node-2":"node-0"}
     *   status, meta_title, ..., ai_generated, ai_improve
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
        $category->load([
            'children' => fn ($q) => $q->orderBy('name')
                ->with([
                    'children' => fn ($q2) => $q2->orderBy('name')
                        ->with([
                            'children' => fn ($q3) => $q3->orderBy('name'),
                        ]),
                ]),
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
            ->route('admin.questions.categories.edit', $category)
            ->with('success', 'Category hierarchy updated successfully.');
    }

    /**
     * Soft-delete a category (children and questions cascade via model hook).
     */
    public function destroy(QuestionCategory $category): RedirectResponse
    {
        $this->service->delete($category);

        return redirect()
            ->route('admin.questions.categories.index')
            ->with('success', "Category \"{$category->name}\" deleted successfully.");
    }
}
