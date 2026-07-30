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
use Illuminate\View\View;

class AuthorController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(Request $request): View|JsonResponse
    {
        $orgId = current_organization_id();

        $authorIds = UserOrganization::query()
            ->whereIn('role', [OrganizationRoles::ADMIN, OrganizationRoles::ORG_ADMIN, OrganizationRoles::EDITOR])
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->pluck('user_id')
            ->unique();

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

        if ($this->wantsFrontendJson($request)) {
            return $this->paginatedHtmlJson($authors, 'frontend.components.author-card', 'author');
        }

        return view('frontend.authors.index', compact('authors'));
    }

    public function show(User $author): View
    {
        abort_unless(
            $author->status === 'active' && filled($author->slug),
            404
        );

        $orgId = current_organization_id();
        $isPublicAuthor = UserOrganization::query()
            ->where('user_id', $author->id)
            ->whereIn('role', [OrganizationRoles::ADMIN, OrganizationRoles::ORG_ADMIN, OrganizationRoles::EDITOR])
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->exists();

        abort_unless($isPublicAuthor, 404);

        $author->load('profile');

        $blogs = Blog::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('author_id', $author->id)
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        $news = News::query()
            ->published()
            ->when($orgId, fn ($q) => $q->forOrg($orgId))
            ->where('author_id', $author->id)
            ->orderByDesc('published_at')
            ->limit(12)
            ->get();

        return view('frontend.authors.show', [
            'author' => $author,
            'blogs' => $blogs,
            'news' => $news,
        ]);
    }
}
