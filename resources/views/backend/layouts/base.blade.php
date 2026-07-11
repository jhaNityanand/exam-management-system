<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title')@else{{ config('app.name', 'ExamMS') }}@endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div id="page-progress" class="page-progress" aria-hidden="true"></div>
    @yield('body')
    @include('partials.flash-toasts')
    @stack('scripts')
    <script>
        (function () {
            const bar = document.getElementById('page-progress');
            if (!bar) return;

            let timer = null;
            const start = () => {
                bar.classList.add('is-active');
                bar.style.width = '18%';
                clearInterval(timer);
                timer = setInterval(() => {
                    const current = parseFloat(bar.style.width) || 18;
                    if (current < 86) bar.style.width = (current + Math.random() * 8) + '%';
                }, 280);
            };
            const done = () => {
                clearInterval(timer);
                bar.style.width = '100%';
                setTimeout(() => {
                    bar.classList.remove('is-active');
                    bar.style.width = '0%';
                }, 220);
            };

            document.addEventListener('click', (e) => {
                const link = e.target.closest('a[href]');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey) return;
                try {
                    const url = new URL(href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    if (url.pathname === window.location.pathname && url.search === window.location.search) return;
                } catch (_) { return; }
                start();
            });

            window.addEventListener('pageshow', done);
        })();
    </script>
</body>
</html>
