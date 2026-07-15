<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Cms\ContactMessage;
use App\Models\Cms\SitePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    use RespondsWithFrontendJson;

    public function show(string $slug): View
    {
        $orgId = $this->organizationId();

        $page = SitePage::query()
            ->published()
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->where('slug', $slug)
            ->with('bannerImage')
            ->firstOrFail();

        return view('frontend.pages.show', [
            'page' => $page,
        ]);
    }

    public function contact(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = ContactMessage::query()->create([
            ...$validated,
            'organization_id' => $this->organizationId(),
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        if ($this->wantsFrontendJson($request)) {
            return response()->json([
                'message' => 'Thank you. Your message has been sent.',
                'data' => [
                    'id' => $message->id,
                ],
            ], 201);
        }

        return back()->with('success', 'Thank you. Your message has been sent.');
    }
}
