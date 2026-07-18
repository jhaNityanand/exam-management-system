<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\SlugService;
use App\Support\UniqueOrgSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SlugController extends Controller
{
    public function __construct(protected SlugService $slugs) {}

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'string', Rule::in($this->slugs->modules())],
            'source' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'string', 'max:255'],
            'ignore_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $orgId = current_organization_id();
        abort_unless($orgId !== null, 403);

        $source = trim((string) ($validated['slug'] ?? ''));
        if ($source === '') {
            $source = trim((string) ($validated['source'] ?? ''));
        }

        $slug = $this->slugs->resolve(
            $validated['module'],
            $source,
            (int) $orgId,
            isset($validated['ignore_id']) ? (int) $validated['ignore_id'] : null,
        );

        return response()->json([
            'slug' => $slug,
            'normalized' => UniqueOrgSlug::normalize($source),
        ]);
    }
}
