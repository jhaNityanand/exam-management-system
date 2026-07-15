<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Blog\StoreBlogRequest;
use App\Http\Requests\Backend\Blog\UpdateBlogRequest;
use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\BlogCategoryService;
use App\Services\BlogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected BlogService $service,
        protected BlogCategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();

        return view('backend.blogs.index', [
            'categories' => $this->categoryService->getHierarchicalList($orgId),
            'tags' => BlogTag::forOrg($orgId)->orderBy('name')->get(['id', 'name']),
            'authors' => $this->orgAuthors($orgId),
            'statuses' => Blog::statuses(),
        ]);
    }

    public function create(): View
    {
        $orgId = $this->currentOrgId();

        return view('backend.blogs.create', [
            'categories' => $this->categoryService->getHierarchicalList($orgId),
            'tags' => BlogTag::forOrg($orgId)->orderBy('name')->get(['id', 'name']),
            'authors' => $this->orgAuthors($orgId),
            'statuses' => Blog::statuses(),
        ]);
    }

    public function store(StoreBlogRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data = $this->prepareData($request->validated());

        $blog = $this->service->create($data, $orgId);

        return redirect()
            ->route('admin.blogs.show', $blog)
            ->with('success', 'Blog post created successfully.');
    }

    public function show(Blog $blog): View
    {
        $this->authorizeBlog($blog);

        $blog->load([
            'category',
            'author',
            'bannerImage',
            'banners',
            'ogImage',
            'tags',
            'galleryAttachments',
        ]);

        return view('backend.blogs.show', compact('blog'));
    }

    public function edit(Blog $blog): View
    {
        $this->authorizeBlog($blog);
        $orgId = $this->currentOrgId();

        $blog->load(['tags', 'galleryAttachments', 'bannerImage', 'banners', 'ogImage']);

        return view('backend.blogs.edit', [
            'blog' => $blog,
            'categories' => $this->categoryService->getHierarchicalList($orgId),
            'tags' => BlogTag::forOrg($orgId)->orderBy('name')->get(['id', 'name']),
            'authors' => $this->orgAuthors($orgId),
            'statuses' => Blog::statuses(),
        ]);
    }

    public function update(UpdateBlogRequest $request, Blog $blog): RedirectResponse
    {
        $this->authorizeBlog($blog);
        $data = $this->prepareData($request->validated());

        $this->service->update($blog, $data);

        return redirect()
            ->route('admin.blogs.show', $blog)
            ->with('success', 'Blog post updated successfully.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->authorizeBlog($blog);
        $this->service->delete($blog);

        return redirect()
            ->route('admin.blogs.index')
            ->with('success', 'Blog post moved to bin.');
    }

    public function restore(int $id): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $blog = Blog::withTrashed()->forOrg($orgId)->findOrFail($id);
        abort_unless($blog->trashed(), 404, 'Blog post is not in the bin.');

        $this->service->restore($blog);

        return redirect()
            ->route('admin.blogs.index')
            ->with('success', 'Blog post restored successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $ids = $this->validatedIds($request);
        $count = $this->service->bulkDelete($orgId, $ids);

        return redirect()
            ->route('admin.blogs.index')
            ->with('success', "{$count} blog post(s) moved to bin.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $ids = $this->validatedIds($request);
        $count = $this->service->bulkRestore($orgId, $ids);

        return redirect()
            ->route('admin.blogs.index', ['tab' => 'bin'])
            ->with('success', "{$count} blog post(s) restored.");
    }

    protected function authorizeBlog(Blog $blog): void
    {
        abort_if($blog->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this blog post.');
    }

    /**
     * @return list<\App\Models\User>
     */
    protected function orgAuthors(int $orgId): array
    {
        $userIds = UserOrganization::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareData(array $data): array
    {
        $map = [
            'meta_title' => 'seo_title',
            'meta_description' => 'seo_description',
            'meta_keywords' => 'seo_keywords',
        ];

        foreach ($map as $from => $to) {
            if (array_key_exists($from, $data)) {
                $data[$to] = $data[$from];
                unset($data[$from]);
            }
        }

        $data['ai_generated'] = (bool) ($data['ai_generated'] ?? false);
        $data['ai_improve'] = (bool) ($data['ai_improve'] ?? false);

        return $data;
    }

    /**
     * @return list<int>
     */
    protected function validatedIds(Request $request): array
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_filter(array_map('intval', $validated['ids']))));
    }
}
