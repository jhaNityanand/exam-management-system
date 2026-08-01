<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Friendly 403 response for web users (candidates / guests).
 * Keeps the requested URL and avoids Laravel's generic error page.
 */
final class PermissionDeniedResponse
{
    public static function message(): string
    {
        return 'You do not have permission to access this page.';
    }

    public static function toResponse(
        Request $request,
        ?string $message = null,
        ?string $title = null,
    ): JsonResponse|Response {
        $message ??= self::message();
        $title ??= 'Access denied';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        return response()->view('errors.permission', [
            'title' => $title,
            'message' => $message,
            'code' => '403',
            'showLogin' => ! $request->user(),
            'showAccount' => (bool) $request->user(),
        ], SymfonyResponse::HTTP_FORBIDDEN);
    }
}
