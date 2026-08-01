<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-default="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title')@else{{ config('app.name', 'ExamMS') }}@endif</title>
    <link rel="icon" href="{{ asset('images/brand/admin-favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/admin-mark.svg') }}">
    <meta name="theme-color" content="#0f766e" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0d9488" media="(prefers-color-scheme: dark)">
    @include('partials.theme-init', ['themeStorageKey' => 'ems.theme', 'themeResolveMode' => 'preference'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ versioned_asset('css/components/icon-buttons.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('vendor/tom-select/tom-select.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('vendor/tom-select/tom-select.default.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/searchable-select.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/tom-select-theme.css') }}">
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
    <script src="{{ versioned_asset('vendor/tom-select/tom-select.complete.min.js') }}"></script>
    <script src="{{ versioned_asset('js/components/searchable-select.js') }}" defer></script>
    @stack('scripts')
    <script src="{{ versioned_asset('js/core/page-progress.js') }}" defer></script>
</body>
</html>
