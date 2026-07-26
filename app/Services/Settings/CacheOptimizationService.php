<?php

namespace App\Services\Settings;

use App\Services\Seo\SeoSiteGenerator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class CacheOptimizationService
{
    /**
     * Whitelisted optimization actions available from the admin UI.
     *
     * @var array<string, array{label: string, description: string, confirm?: string, danger?: bool}>
     */
    public const ACTIONS = [
        'clear_app_cache' => [
            'label' => 'Clear Application Cache',
            'description' => 'Flush the application cache store (cache:clear).',
        ],
        'clear_config_cache' => [
            'label' => 'Clear Config Cache',
            'description' => 'Remove the cached configuration file (config:clear).',
        ],
        'clear_route_cache' => [
            'label' => 'Clear Route Cache',
            'description' => 'Remove the cached route file (route:clear).',
        ],
        'clear_view_cache' => [
            'label' => 'Clear View Cache',
            'description' => 'Clear compiled Blade views (view:clear).',
        ],
        'clear_event_cache' => [
            'label' => 'Clear Event Cache',
            'description' => 'Clear cached events and listeners (event:clear).',
        ],
        'optimize' => [
            'label' => 'Optimize Application',
            'description' => 'Cache config, events, routes, and views for production (optimize).',
            'confirm' => 'This caches config/routes for production. Local .env changes will not apply until you clear optimization again.',
        ],
        'optimize_clear' => [
            'label' => 'Optimize Clear',
            'description' => 'Remove all cached bootstrap files (optimize:clear).',
        ],
        'storage_link' => [
            'label' => 'Storage Link',
            'description' => 'Create the public/storage symlink for uploaded media (storage:link).',
        ],
        'clear_temp' => [
            'label' => 'Clear Temporary Files',
            'description' => 'Remove framework cache data, compiled views, and temporary files under storage/framework.',
            'confirm' => 'Temporary framework files will be deleted. Active users may briefly see slower first loads.',
        ],
        'clear_logs' => [
            'label' => 'Clear Logs',
            'description' => 'Delete application log files in storage/logs.',
            'confirm' => 'All log files in storage/logs will be permanently deleted.',
            'danger' => true,
        ],
        'regenerate_sitemap' => [
            'label' => 'Regenerate Sitemap',
            'description' => 'Rebuild sitemap index, robots.txt, feeds, and related SEO files (seo:generate).',
        ],
    ];

    /**
     * @return array{success: bool, action: string, label: string, output: string, exit_code: int, duration_ms: int, meta?: array<string, mixed>}
     */
    public function run(string $action): array
    {
        if (! isset(self::ACTIONS[$action])) {
            return [
                'success' => false,
                'action' => $action,
                'label' => $action,
                'output' => 'Unknown optimization action.',
                'exit_code' => 1,
                'duration_ms' => 0,
            ];
        }

        $started = microtime(true);
        $label = self::ACTIONS[$action]['label'];

        try {
            $result = match ($action) {
                'clear_app_cache' => $this->artisan('cache:clear'),
                'clear_config_cache' => $this->artisan('config:clear'),
                'clear_route_cache' => $this->artisan('route:clear'),
                'clear_view_cache' => $this->artisan('view:clear'),
                'clear_event_cache' => $this->artisan('event:clear'),
                'optimize' => $this->artisan('optimize'),
                'optimize_clear' => $this->artisan('optimize:clear'),
                'storage_link' => $this->storageLink(),
                'clear_temp' => $this->clearTemporaryFiles(),
                'clear_logs' => $this->clearLogs(),
                'regenerate_sitemap' => $this->regenerateSitemap(),
                default => ['exit_code' => 1, 'output' => 'Unhandled action.', 'meta' => []],
            };
        } catch (Throwable $e) {
            $result = [
                'exit_code' => 1,
                'output' => $e->getMessage(),
                'meta' => [],
            ];
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);

        return [
            'success' => ((int) ($result['exit_code'] ?? 1)) === 0,
            'action' => $action,
            'label' => $label,
            'output' => trim((string) ($result['output'] ?? '')),
            'exit_code' => (int) ($result['exit_code'] ?? 1),
            'duration_ms' => $durationMs,
            'meta' => $result['meta'] ?? [],
        ];
    }

    /**
     * @return list<array{key: string, label: string, description: string, confirm: ?string, danger: bool}>
     */
    public function catalog(): array
    {
        $items = [];
        foreach (self::ACTIONS as $key => $meta) {
            $items[] = [
                'key' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'confirm' => $meta['confirm'] ?? null,
                'danger' => (bool) ($meta['danger'] ?? false),
            ];
        }

        return $items;
    }

    /**
     * @return array{exit_code: int, output: string, meta: array<string, mixed>}
     */
    protected function artisan(string $command, array $parameters = []): array
    {
        $exitCode = Artisan::call($command, $parameters);
        $output = Artisan::output();

        return [
            'exit_code' => $exitCode,
            'output' => $output !== '' ? $output : "Command [{$command}] completed successfully.",
            'meta' => ['command' => $command],
        ];
    }

    /**
     * @return array{exit_code: int, output: string, meta: array<string, mixed>}
     */
    protected function storageLink(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        File::ensureDirectoryExists($target);

        if (is_link($link) || (windows_os() && is_dir($link) && ! is_file($link))) {
            // Already linked — report success without failing.
            if (is_link($link) || file_exists($link)) {
                return [
                    'exit_code' => 0,
                    'output' => 'The [public/storage] link already exists.',
                    'meta' => ['link' => $link, 'target' => $target, 'already_exists' => true],
                ];
            }
        }

        try {
            $result = $this->artisan('storage:link');
            if ($result['exit_code'] !== 0 && (is_link($link) || file_exists($link))) {
                $result['exit_code'] = 0;
                $result['output'] = 'The [public/storage] link is available.';
            }

            return $result;
        } catch (Throwable $e) {
            if (file_exists($link)) {
                return [
                    'exit_code' => 0,
                    'output' => 'The [public/storage] link already exists.',
                    'meta' => ['already_exists' => true],
                ];
            }

            throw $e;
        }
    }

    /**
     * @return array{exit_code: int, output: string, meta: array<string, mixed>}
     */
    protected function clearTemporaryFiles(): array
    {
        $deleted = 0;
        $paths = [
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('framework/testing'),
            storage_path('framework/tmp'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }
            $deleted += $this->deleteDirectoryContents($path);
        }

        // Compiled package discovery caches (safe to rebuild).
        foreach (File::glob(storage_path('framework/cache/*.php')) ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $deleted++;
            }
        }

        return [
            'exit_code' => 0,
            'output' => "Cleared temporary framework files. Removed {$deleted} file(s)/folder(s).",
            'meta' => ['deleted' => $deleted],
        ];
    }

    /**
     * @return array{exit_code: int, output: string, meta: array<string, mixed>}
     */
    protected function clearLogs(): array
    {
        $deleted = 0;
        $bytes = 0;
        $logPath = storage_path('logs');

        if (is_dir($logPath)) {
            foreach (File::files($logPath) as $file) {
                if (strtolower($file->getExtension()) !== 'log') {
                    continue;
                }
                $bytes += $file->getSize();
                if (@unlink($file->getPathname())) {
                    $deleted++;
                }
            }
        }

        return [
            'exit_code' => 0,
            'output' => "Cleared {$deleted} log file(s) (". $this->formatBytes($bytes).').',
            'meta' => ['deleted' => $deleted, 'bytes' => $bytes],
        ];
    }

    /**
     * @return array{exit_code: int, output: string, meta: array<string, mixed>}
     */
    protected function regenerateSitemap(): array
    {
        $orgId = current_organization_id();
        if (! $orgId) {
            return [
                'exit_code' => 1,
                'output' => 'No organization found. Seed the database first.',
                'meta' => [],
            ];
        }

        $result = app(SeoSiteGenerator::class)->generate($orgId);
        $counts = $result['url_counts'] ?? [];
        $summary = collect($counts)->map(fn ($count, $section) => "{$section}: {$count}")->implode(', ');

        return [
            'exit_code' => 0,
            'output' => 'SEO files regenerated at '.$result['generated_at'].($summary !== '' ? " ({$summary})" : ''),
            'meta' => $result,
        ];
    }

    protected function deleteDirectoryContents(string $directory): int
    {
        $count = 0;

        foreach (File::directories($directory) as $dir) {
            // Keep .gitignore placeholders by deleting contents, not the root.
            if (str_ends_with($dir, DIRECTORY_SEPARATOR.'.') || str_ends_with($dir, '/.')) {
                continue;
            }
            File::deleteDirectory($dir);
            $count++;
        }

        foreach (File::files($directory) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }
            if (@unlink($file->getPathname())) {
                $count++;
            }
        }

        return $count;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
