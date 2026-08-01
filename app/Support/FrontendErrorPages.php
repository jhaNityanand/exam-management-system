<?php

namespace App\Support;

use App\Http\Responses\PermissionDeniedResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders branded frontend error pages so visitors never see framework defaults.
 */
final class FrontendErrorPages
{
    public const STATUSES = [403, 404, 419, 429, 500, 503];

    /**
     * @return array{title: string, message: string, show_search?: bool, show_refresh?: bool, show_login?: bool, show_account?: bool}
     */
    public static function meta(int $status, ?string $overrideMessage = null): array
    {
        $meta = match ($status) {
            403 => [
                'title' => 'Access denied',
                'message' => PermissionDeniedResponse::message(),
                'show_login' => true,
                'show_account' => true,
            ],
            404 => [
                'title' => 'Page not found',
                'message' => 'We looked everywhere, but this page seems to have taken a break. Try searching or head back home.',
                'show_search' => true,
            ],
            419 => [
                'title' => 'Page expired',
                'message' => 'Your session timed out for security. Refresh the page and try again.',
                'show_refresh' => true,
            ],
            429 => [
                'title' => 'Too many requests',
                'message' => 'You are moving a little too fast. Please wait a moment and try again.',
            ],
            500 => [
                'title' => 'Something went wrong',
                'message' => 'Our servers hit an unexpected bump. We are on it — please try again shortly.',
            ],
            503 => [
                'title' => 'Service unavailable',
                'message' => 'We are temporarily offline for maintenance. Please check back in a few minutes.',
            ],
            default => [
                'title' => 'Something went wrong',
                'message' => 'Please try again or return to the homepage.',
            ],
        };

        if ($status === 403 && is_string($overrideMessage) && trim($overrideMessage) !== '') {
            $meta['message'] = trim($overrideMessage);
        }

        return $meta;
    }

    public static function shouldHandle(Request $request, Throwable $e): bool
    {
        if ($request->expectsJson()) {
            return false;
        }

        // Let Laravel handle these with redirects / form errors.
        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            return false;
        }

        $status = self::statusFrom($e);
        if (! in_array($status, self::STATUSES, true)) {
            return false;
        }

        // Keep Laravel’s detailed debug screen for unexpected admin errors.
        if (
            $status === 500
            && config('app.debug')
            && $request->is('admin', 'admin/*')
            && ! $e instanceof HttpExceptionInterface
        ) {
            return false;
        }

        return true;
    }

    public static function statusFrom(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof TokenMismatchException) {
            return 419;
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 404;
        }

        return 500;
    }

    public static function render(Request $request, Throwable $e): ?Response
    {
        if (! self::shouldHandle($request, $e)) {
            return null;
        }

        $status = self::statusFrom($e);

        $message = null;
        if ($status === 403 && $e->getMessage() !== '') {
            $message = $e->getMessage();
        }

        if ($status === 403) {
            return PermissionDeniedResponse::toResponse($request, $message);
        }

        if (! view()->exists('errors.'.$status)) {
            return null;
        }

        $meta = self::meta($status, $message);

        return response()->view('errors.'.$status, [
            'exception' => $e,
            'title' => $meta['title'],
            'message' => $meta['message'],
        ], $status);
    }
}
