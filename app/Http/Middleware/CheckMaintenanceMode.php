<?php

namespace App\Http\Middleware;

use App\Services\Settings\MaintenanceModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks public frontend traffic when maintenance mode is enabled.
 *
 * Admin panel routes and authenticated admins continue to work normally.
 * Auth entry points (login / password reset) stay available so staff can sign in.
 */
class CheckMaintenanceMode
{
    public function __construct(
        protected MaintenanceModeService $maintenance,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $config = $this->maintenance->get();

        if (! ($config['enabled'] ?? false)) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $response = response()
            ->view('frontend.maintenance', ['maintenance' => $config], 503);

        $unix = $config['estimated_at_unix'] ?? null;
        if (is_int($unix) && $unix > 0) {
            $response->headers->set('Retry-After', (string) max(0, $unix - time()));
        } else {
            $response->headers->set('Retry-After', '3600');
        }

        return $response;
    }

    protected function shouldBypass(Request $request): bool
    {
        // Backend admins (any role that can use the admin panel) keep full access.
        if (user_can_access_admin()) {
            return true;
        }

        // Admin area + staff auth flows stay reachable for everyone who needs to log in.
        // Public registration and all other frontend routes remain blocked.
        if ($request->is(
            'admin',
            'admin/*',
            'login',
            'logout',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'up',
            'docs',
            'docs/*',
            'sanctum/csrf-cookie',
            'sitemap.xml',
            'image-sitemap.xml',
            'sitemaps',
            'sitemaps/*',
            'robots.txt',
            'humans.txt',
            'manifest.json',
            'site.webmanifest',
            'feeds',
            'feeds/*',
            '.well-known',
            '.well-known/*',
        )) {
            return true;
        }

        return false;
    }
}
