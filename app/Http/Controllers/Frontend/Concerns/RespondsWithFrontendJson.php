<?php

namespace App\Http\Controllers\Frontend\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

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
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Return paginated JSON with rendered Blade card HTML for Load More.
     */
    protected function paginatedHtmlJson(
        LengthAwarePaginator $paginator,
        string $cardView,
        string $itemKey
    ): JsonResponse {
        $html = '';
        foreach ($paginator->items() as $item) {
            $html .= View::make($cardView, [$itemKey => $item])->render();
        }

        return response()->json([
            'html' => $html,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'next_page_url' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    protected function organizationId(): ?int
    {
        return current_organization_id();
    }
}
