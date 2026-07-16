<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Concerns\HandlesCategoryListActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\BlogCategory\StoreBlogCategoryRequest;
use App\Http\Requests\Backend\BlogCategory\UpdateBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Services\BlogCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BlogCategoryController
 *
 * Handles CRUD for Blog Categories. Categories are scoped to
 * the authenticated user's organization.
 *
 * Route prefix : admin/blogs/categories
 * Route names  : admin.blogs.categories.{index|create|store|edit|update|destroy}
 */
class BlogCategoryController extends Controller
{
    use HandlesCategoryListActions, ResolvesCurrentOrganization;

    protected function categoryModelClass(): string { return BlogCategory::class; }
    protected function categoryIndexRoute(): string { return 'admin.blogs.categories.index'; }

    public function __construct(
        protected BlogCategoryService $service
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
            $query = BlogCategory::forOrg($orgId);
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

            return view('backend.blog-categories.partials.tree-list', compact('categories'))->render();
        }

        $categories = collect([]);
        return view('backend.blog-categories.index', compact(
            'categories',
            'search',
            'status',
            'sort',
        ));
    }

    /**
     * Display the specified category details in JSON.
     */
    public function show(BlogCategory $category): \Illuminate\Http\JsonResponse
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
        return view('backend.blog-categories.create');
    }

    /**
     * Persist the category tree created by the builder form.
     */
    public function store(StoreBlogCategoryRequest $request): RedirectResponse
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
            ->route('admin.blogs.categories.index')
            ->with('success', 'Blog category hierarchy saved successfully.');
    }

    /**
     * Show the edit form for a single category.
     */
    public function edit(BlogCategory $category): View
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $category->load([
            'childrenRecursive' => fn ($q) => $q->orderBy('name'),
        ]);

        return view('backend.blog-categories.edit', compact('category'));
    }

    /**
     * Persist category edits.
     */
    public function update(
        UpdateBlogCategoryRequest $request,
        BlogCategory              $category,
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
            ->route('admin.blogs.categories.index')
            ->with('success', 'Blog category hierarchy updated successfully.');
    }

    /**
     * Soft-delete a category.
     */
    public function destroy(BlogCategory $category): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        abort_if($category->organization_id !== $orgId, 403, 'Unauthorized access to this category.');

        $this->service->delete($category);

        return redirect()
            ->route('admin.blogs.categories.index')
            ->with('success', "Blog category \"{$category->name}\" deleted successfully.");
    }
}
