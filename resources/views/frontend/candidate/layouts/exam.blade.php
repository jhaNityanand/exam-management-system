<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Exam') — {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="@yield('title', 'Exam')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Exam') — {{ config('app.name') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ seo_default_image('exam') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ seo_default_image('exam') }}">
    @php
        $faviconUrl = $siteBrand['favicon_url'] ?? null;
        if (! filled($faviconUrl)) {
            $faviconUrl = asset('images/brand/favicon.svg');
        }
        $faviconType = str_ends_with(parse_url($faviconUrl, PHP_URL_PATH) ?? '', '.svg') ? 'image/svg+xml' : 'image/png';
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    @include('frontend.partials.theme-init')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/candidate-exam.css') }}">
    @stack('styles')
</head>
<body class="cx-body">
    <script src="{{ versioned_asset('js/frontend/theme-boot.js') }}" data-sync-body="1"></script>
    <a class="et-skip-link" href="#cx-main">Skip to exam content</a>
    @yield('content')
    @include('partials.flash-toasts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="{{ versioned_asset('js/frontend/utils.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
