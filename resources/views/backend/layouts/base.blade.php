<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-default="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title')@else{{ config('app.name', 'ExamMS') }}@endif</title>
    <link rel="icon" href="{{ versioned_asset('images/brand/admin-favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ versioned_asset('images/brand/admin-mark.svg') }}">
    <meta name="theme-color" content="#0f766e" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0d9488" media="(prefers-color-scheme: dark)">
    @include('partials.theme-init', ['themeStorageKey' => 'ems.theme', 'themeResolveMode' => 'preference'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ versioned_asset('css/components/icon-buttons.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('vendor/tom-select/tom-select.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('vendor/tom-select/tom-select.default.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/searchable-select.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/tom-select-theme.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/tooltip.css') }}">
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <script>
        (function () {
            function markThemeReady() {
                document.documentElement.classList.add('ems-theme-ready');
                document.documentElement.style.backgroundColor = '';
            }
            if (document.readyState === 'complete') markThemeReady();
            else window.addEventListener('load', markThemeReady);
        })();
    </script>
    <div id="page-progress" class="page-progress" aria-hidden="true"></div>
    @yield('body')
    @include('partials.flash-toasts')

    <button
        type="button"
        class="admin-back-top"
        data-admin-back-top
        aria-label="Scroll to top"
        title="Back to top"
        hidden
        aria-hidden="true"
    >
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <script src="{{ versioned_asset('vendor/tom-select/tom-select.complete.min.js') }}"></script>
    <script src="{{ versioned_asset('js/components/select-config.js') }}"></script>
    <script src="{{ versioned_asset('js/components/searchable-select.js') }}" defer></script>
    <script src="{{ versioned_asset('js/components/tooltip.js') }}" defer></script>
    <script src="{{ versioned_asset('js/core/form-utils.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/back-to-top.js') }}" defer></script>
    @stack('scripts')
    <script src="{{ versioned_asset('js/core/page-progress.js') }}" defer></script>
</body>
</html>
