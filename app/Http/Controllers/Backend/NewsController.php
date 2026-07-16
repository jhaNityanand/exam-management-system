<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\News\StoreNewsRequest;
use App\Http\Requests\Backend\News\UpdateNewsRequest;
use App\Models\News;
use App\Models\NewsTag;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\NewsCategoryService;
use App\Services\NewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected NewsService $service,
        protected NewsCategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $orgId = $this->currentOrgId();

        return view('backend.news.index', [
            'categories' => $this->categoryService->getHierarchicalList($orgId),
            'tags' => NewsTag::forOrg($orgId)->orderBy('name')->get(['id', 'name']),
            'authors' => $this->orgAuthors($orgId),
            'statuses' => News::statuses(),
            'visibilities' => News::visibilities(),
        ]);
    }

    public function create(): View
    {
        $orgId = $this->currentOrgId();

        return view('backend.news.create', [
            'categories' => $this->categoryService->getHierarchicalList($orgId),
            'tags' => NewsTag::forOrg($orgId)->orderBy('name')->get(['id', 'name']),
            'authors' => $this->orgAuthors($orgId),
            'statuses' => News::statuses(),
            'visibilities' => News::visibilities(),
        ]);
    }

    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $data = $this->prepareData($request->validated());

        $news = $this->service->create($data, $orgId);

        return redirect()
            ->route('admin.news.show', $news)
            ->with('success', 'News item created successfully.');
    }

    public function show(News $news): View
    {
        $this->authorizeNews($news);

        $news->load([
            'category',
            'author',
            'bannerImage',
            'featuredImage',
            'banners',
            'ogImage',
            'tags',
            'galleryAttachments',
        ]);

        return view('backend.news.show', compact('news'));
    }

    public function edit(News $news): View
    {
        $this->authorizeNews($news);
        $orgId = $this->currentOrgId();

        $news->load(['tags', 'galleryAttachments', 'bannerImage', 'featuredImage', 'banners', 'ogImage']);

        return view('backend.news.edit', [
            'news' => $news,
            'categories' => $this->categoryService->getHierarchicalList($orgId),
            'tags' => NewsTag::forOrg($orgId)->orderBy('name')->get(['id', 'name']),
            'authors' => $this->orgAuthors($orgId),
            'statuses' => News::statuses(),
            'visibilities' => News::visibilities(),
        ]);
    }

    public function update(UpdateNewsRequest $request, News $news): RedirectResponse
    {
        $this->authorizeNews($news);
        $data = $this->prepareData($request->validated());

        $this->service->update($news, $data);

        return redirect()
            ->route('admin.news.show', $news)
            ->with('success', 'News item updated successfully.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->authorizeNews($news);
        $this->service->delete($news);

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'News item moved to bin.');
    }

    public function restore(int $id): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $item = News::withTrashed()->forOrg($orgId)->findOrFail($id);
        abort_unless($item->trashed(), 404, 'News item is not in the bin.');

        $this->service->restore($item);

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'News item restored successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $ids = $this->validatedIds($request);
        $count = $this->service->bulkDelete($orgId, $ids);

        return redirect()
            ->route('admin.news.index')
            ->with('success', "{$count} news item(s) moved to bin.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $ids = $this->validatedIds($request);
        $count = $this->service->bulkRestore($orgId, $ids);

        return redirect()
            ->route('admin.news.index', ['tab' => 'bin'])
            ->with('success', "{$count} news item(s) restored.");
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(array_keys(News::statuses()))],
        ]);
        $count = News::forOrg($this->currentOrgId())
            ->whereIn('id', array_unique($validated['ids']))
            ->update(['status' => $validated['status']]);

        return redirect()->route('admin.news.index')
            ->with('success', "Status updated for {$count} news item(s).");
    }

    protected function authorizeNews(News $news): void
    {
        abort_if($news->organization_id !== $this->currentOrgId(), 403, 'Unauthorized access to this news item.');
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
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_breaking'] = (bool) ($data['is_breaking'] ?? false);
        $data['is_trending'] = (bool) ($data['is_trending'] ?? false);

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
