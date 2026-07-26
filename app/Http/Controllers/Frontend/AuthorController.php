<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\News;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $orgId = current_organization_id();

        $authorIds = UserOrganization::query()
            ->whereIn('role', [OrganizationRoles::ADMIN, OrganizationRoles::ORG_ADMIN, OrganizationRoles::EDITOR])
            ->where('status', 'active')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->pluck('user_id')
            ->unique();

        $authors = User::query()
            ->with('profile')
            ->whereIn('id', $authorIds)
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->orderBy('name')
            ->get();

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
