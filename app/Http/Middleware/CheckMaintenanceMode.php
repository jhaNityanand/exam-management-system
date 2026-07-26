<?php

namespace App\Http\Middleware;

use App\Services\Settings\MaintenanceModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function __construct(
        protected MaintenanceModeService $maintenance,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenance->isEnabled()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $config = $this->maintenance->get();

        return response()
            ->view('frontend.maintenance', ['maintenance' => $config], 503)
            ->header('Retry-After', '3600');
    }

    protected function shouldBypass(Request $request): bool
    {
        if (user_can_access_admin()) {
            return true;
        }

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
