<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$paths = ['/', '/faqs', '/sitemap', '/blogs', '/exams', '/questions', '/news', '/authors', '/categories'];
foreach ($paths as $path) {
    $request = Illuminate\Http\Request::create($path, 'GET');
    try {
        $response = $kernel->handle($request);
        $html = $response->getContent();
        echo $path.' => '.$response->getStatusCode();
        if ($path === '/') {
            echo ' nav='.(str_contains($html, 'et-nav__dropdown') ? 'ok' : 'missing');
            echo ' drawer='.(str_contains($html, 'et-drawer') ? 'ok' : 'missing');
            echo ' footer='.(str_contains($html, 'et-footer__contact') ? 'ok' : 'missing');
            echo ' hero='.(str_contains($html, 'et-hero') ? 'ok' : 'missing');
            echo ' seo='.(str_contains($html, 'application/ld+json') ? 'ok' : 'missing');
            echo ' ads='.(str_contains($html, 'ad-slot') || str_contains($html, 'et-ad') ? 'present' : 'none');
        }
        echo PHP_EOL;
    } catch (Throwable $e) {
        echo $path.' => ERROR '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine().PHP_EOL;
    }
}
