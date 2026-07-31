<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Exam') — {{ config('app.name') }}</title>
    @include('frontend.partials.theme-init')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/ads.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/candidate-exam.css') }}">
    @stack('styles')
</head>
<body class="cx-body">
    <script>
        (function () {
            var actual = (document.documentElement.dataset.themeActual
                || document.documentElement.dataset.theme
                || 'light') === 'dark' ? 'dark' : 'light';
            document.body.setAttribute('data-theme', actual);
            document.documentElement.classList.toggle('dark', actual === 'dark');
            document.documentElement.dataset.theme = actual;
            document.documentElement.dataset.themeActual = actual;
            document.documentElement.style.colorScheme = actual;

            function markThemeReady() {
                document.documentElement.classList.add('ems-theme-ready');
                document.documentElement.style.backgroundColor = '';
            }
            if (document.readyState === 'complete') markThemeReady();
            else window.addEventListener('load', markThemeReady);
        })();
    </script>
    @yield('content')
    @include('partials.flash-toasts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
</body>
</html>
