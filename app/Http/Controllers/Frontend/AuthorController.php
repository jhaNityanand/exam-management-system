<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Blog;
use App\Models\News;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AuthorController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = current_organization_id();
        $authorRoles = OrganizationRoles::adminPanelRoles();

        $membershipQuery = UserOrganization::query()
            ->whereIn('role', $authorRoles)
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId));

        if ($request->filled('role') && in_array($request->input('role'), $authorRoles, true)) {
            $membershipQuery->where('role', $request->input('role'));
        }

        $authorIds = $membershipQuery->pluck('user_id')->unique();

        $query = User::query()
            ->with('profile')
            ->whereIn('id', $authorIds)
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhereHas('profile', fn ($profile) => $profile->where('bio', 'like', $term));
                });
            });

        match ($request->input('sort', 'name')) {
            'name_desc' => $query->orderByDesc('name'),
            'latest' => $query->latest('id'),
            default => $query->orderBy('name'),
        };

        $authors = $query
            ->paginate((int) $request->input('per_page', 36))
            ->withQueryString();

        $this->attachPublicRoles($authors->getCollection(), $orgId);

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($authors, 'frontend.components.author-card', 'author');
        }

        return view('frontend.authors.index', [
            'authors' => $authors,
            'roleOptions' => OrganizationRoles::publicAuthorLabels(),
        ]);
    }

    public function show(User $author): View
    {
        abort_unless(
            $author->status === 'active' && filled($author->slug),
            404
        );

        $orgId = current_organization_id();
        $membership = UserOrganization::query()
            ->where('user_id', $author->id)
            ->whereIn('role', OrganizationRoles::adminPanelRoles())
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get()
            ->sortBy(fn ($row) => array_search($row->role, OrganizationRoles::adminPanelRoles(), true))
            ->first();

        abort_unless($membership, 404);

        $author->load('profile');
        $author->setAttribute('public_role', $membership->role);

        $blogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('author_id', $author->id)
            ->with(['category:id,name,slug', 'bannerImage', 'banners'])
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        $news = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('author_id', $author->id)
            ->with(['category:id,name,slug', 'bannerImage', 'featuredImage'])
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        $socialLinks = collect($author->profile?->social_links ?? [])
            ->filter(fn ($value) => filled($value))
            ->all();

        return view('frontend.authors.show', [
            'author' => $author,
            'role' => author_role($author, $membership->role),
            'blogs' => $blogs,
            'news' => $news,
            'socialLinks' => $socialLinks,
            'stats' => [
                'blogs' => $blogs->count(),
                'news' => $news->count(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, User>  $authors
     */
    protected function attachPublicRoles(Collection $authors, ?int $orgId): void
    {
        if ($authors->isEmpty()) {
            return;
        }

        $roles = UserOrganization::query()
            ->whereIn('user_id', $authors->pluck('id'))
            ->whereIn('role', OrganizationRoles::adminPanelRoles())
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get(['user_id', 'role'])
            ->groupBy('user_id')
            ->map(function (Collection $rows) {
                return $rows
                    ->sortBy(fn ($row) => array_search($row->role, OrganizationRoles::adminPanelRoles(), true))
                    ->first()
                    ?->role;
            });

        foreach ($authors as $author) {
            $author->setAttribute('public_role', $roles[$author->id] ?? OrganizationRoles::EDITOR);
        }
    }
}
