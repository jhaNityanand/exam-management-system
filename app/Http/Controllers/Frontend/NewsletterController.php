<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Cms\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    use RespondsWithFrontendJson;

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $orgId = $this->organizationId();

        $subscriber = NewsletterSubscriber::query()->updateOrCreate(
            [
                'organization_id' => $orgId,
                'email' => strtolower($validated['email']),
            ],
            [
                'name' => $validated['name'] ?? null,
                'status' => 'subscribed',
                'source' => 'frontend',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'ip_address' => $request->ip(),
            ]
        );

        if ($this->wantsFrontendJson($request)) {
            return response()->json([
                'message' => 'You are subscribed to our newsletter.',
                'data' => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                ],
            ], 201);
        }

        return back()->with('success', 'You are subscribed to our newsletter.');
    }
}
