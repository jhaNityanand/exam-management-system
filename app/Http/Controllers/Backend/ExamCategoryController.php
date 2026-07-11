<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ExamCategory\StoreExamCategoryRequest;
use App\Http\Requests\Backend\ExamCategory\UpdateExamCategoryRequest;
use App\Models\ExamCategory;
use App\Models\UserOrganization;
use App\Services\ExamCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ExamCategoryController
 *
 * Handles CRUD for Exam Categories. Categories are scoped to
 * the authenticated user's organization.
 *
 * Route prefix : admin/exams/categories
 * Route names  : admin.exams.categories.{index|create|store|edit|update|destroy}
 */
class ExamCategoryController extends Controller
{
    public function __construct(
        protected ExamCategoryService $service
    ) {}

    // ── Organization helper ───────────────────────────────────────────────────

    /**
     * Resolve the authenticated user's active organization ID.
     */
    protected function currentOrgId(): int
    {
        if (Auth::check()) {
            $orgId = UserOrganization::where('user_id', Auth::id())
                ->where('status', 'active')
                ->value('organization_id');

            if ($orgId) {
                return (int) $orgId;
            }
        }

        $id = current_organization_id();
        abort_if($id === null, 503, 'No organization found. Please run the database seeder.');

        return $id;
    }

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

        if ($request->ajax()) {
            $query = ExamCategory::forOrg($orgId);

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

            return view('backend.exam-categories.partials.tree-list', compact('categories'))->render();
        }

        $categories = collect([]);
        return view('backend.exam-categories.index', compact(
            'categories',
            'search',
            'status',
            'sort',
        ));
    }

    /**
     * Display the specified category details in JSON.
     */
    public function show(ExamCategory $category): \Illuminate\Http\JsonResponse
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $category->load([
            'parent',
            'children' => fn ($q) => $q->orderBy('name'),
        ]);

        $category->formatted_created_at = $category->created_at ? $category->created_at->format('M d, Y h:i A') : 'N/A';
        $category->formatted_updated_at = $category->updated_at ? $category->updated_at->format('M d, Y h:i A') : 'N/A';

        return response()->json($category);
    }

    /**
     * Show the category tree-builder form.
     */
    public function create(): View
    {
        return view('backend.exam-categories.create');
    }

    /**
     * Persist the category tree created by the builder form.
     */
    public function store(StoreExamCategoryRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data  = $request->validated();

        $parentMapRaw = $request->input('_parent_map', '{}');
        $parentMap    = json_decode($parentMapRaw, true) ?: [];

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
            ->route('admin.exams.categories.index')
            ->with('success', 'Exam category hierarchy saved successfully.');
    }

    /**
     * Show the edit form for a single category.
     */
    public function edit(ExamCategory $category): View
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $category->load([
            'children' => fn ($q) => $q->orderBy('name')
                ->with([
                    'children' => fn ($q2) => $q2->orderBy('name')
                        ->with([
                            'children' => fn ($q3) => $q3->orderBy('name'),
                        ]),
                ]),
        ]);

        return view('backend.exam-categories.edit', compact('category'));
    }

    /**
     * Persist category edits.
     */
    public function update(
        UpdateExamCategoryRequest $request,
        ExamCategory              $category,
    ): RedirectResponse {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $data = $request->validated();

        $parentMapRaw = $request->input('_parent_map', '{}');
        $parentMap    = json_decode($parentMapRaw, true) ?: [];

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
            ->route('admin.exams.categories.edit', $category)
            ->with('success', 'Exam category hierarchy updated successfully.');
    }

    /**
     * Soft-delete a category.
     */
    public function destroy(ExamCategory $category): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $this->service->delete($category);

        return redirect()
            ->route('admin.exams.categories.index')
            ->with('success', "Exam category \"{$category->name}\" deleted successfully.");
    }
}
