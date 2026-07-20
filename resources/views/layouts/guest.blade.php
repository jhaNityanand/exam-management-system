<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · {{ site_setting('brand.site_name', 'Examtube.in') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/icon-buttons.css') }}">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('examtube-theme');
                if (t === 'system' || !t) {
                    t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                if (t === 'dark') document.documentElement.classList.add('dark');
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
    </script>
</head>
<body class="et-body et-auth-body">
    <div class="et-auth">
        <aside class="et-auth__brand-panel" aria-hidden="false">
            <a href="{{ route('home') }}" class="et-auth__logo">
                @if(is_file(public_path('images/brand/examtube-logo.svg')))
                    <img class="et-logo__img" src="{{ asset('images/brand/examtube-logo.svg') }}" alt="{{ site_setting('brand.site_name', 'Examtube.in') }}" width="150" height="32">
                @else
                    <span class="et-auth__logo-mark">{{ strtoupper(substr(site_setting('brand.logo_text', 'Examtube'), 0, 2)) }}</span>
                    <span>{{ site_setting('brand.logo_text', 'Examtube') }}<em>.in</em></span>
                @endif
            </a>
            <h1 class="et-auth__headline">{{ site_setting('brand.tagline', 'Practice smarter. Score higher.') }}</h1>
            <p class="et-auth__subcopy">
                Structured mocks, mentor blogs, and exam news — built for students, mentors, and institutes.
            </p>
            <ul class="et-auth__bullets">
                <li>Timed exam practice with real scoring rules</li>
                <li>Track attempts and improve week by week</li>
                <li>Stay current with blogs and campus news</li>
            </ul>
        </aside>

        <main class="et-auth__main">
            <div class="et-auth__card">
                <div class="et-auth__mobile-brand">
                    <a href="{{ route('home') }}" class="et-auth__logo">
                        <span class="et-auth__logo-mark">{{ strtoupper(substr(site_setting('brand.logo_text', 'Examtube'), 0, 2)) }}</span>
                        <span>{{ site_setting('brand.logo_text', 'Examtube') }}</span>
                    </a>
                </div>
                {{ $slot }}
            </div>
            <p class="et-auth__footnote">
                <a href="{{ route('home') }}">← Back to Examtube.in</a>
            </p>
        </main>
    </div>
    @include('partials.flash-toasts')
</body>
</html>
