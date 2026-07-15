<?php

namespace App\Http\Controllers\Frontend\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RespondsWithFrontendJson
{
    protected function wantsFrontendJson(Request $request): bool
    {
        return $request->wantsJson()
            || $request->ajax()
            || $request->expectsJson();
    }

    protected function paginatedJson(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    protected function organizationId(): ?int
    {
        return current_organization_id();
    }
}
