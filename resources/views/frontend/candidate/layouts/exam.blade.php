<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Exam') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/candidate-exam.css') }}">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('examtube-theme');
                if (t === 'system' || !t) {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                if (t === 'dark') document.documentElement.classList.add('dark');
                document.documentElement.dataset.theme = t;
                window.__examtubeTheme = t;
            } catch (e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="cx-body">
    <script>
        (function () {
            try {
                var t = window.__examtubeTheme || 'light';
                if (t === 'system') {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                t = t === 'dark' ? 'dark' : 'light';
                document.body.setAttribute('data-theme', t);
                document.documentElement.classList.toggle('dark', t === 'dark');
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
    </script>
    @yield('content')
    @stack('scripts')
</body>
</html>
