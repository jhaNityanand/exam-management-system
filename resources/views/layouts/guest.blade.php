<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · {{ site_setting('brand.site_name', 'Examtube.in') }}</title>
    <link rel="icon" href="{{ asset('images/brand/admin-favicon.svg') }}" type="image/svg+xml">
    <meta name="description" content="{{ site_setting('brand.tagline', 'Practice smarter. Score higher.') }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Sign in') · {{ site_setting('brand.site_name', 'Examtube.in') }}">
    <meta property="og:description" content="{{ site_setting('brand.tagline', 'Practice smarter. Score higher.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ seo_default_image('organization') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Sign in') · {{ site_setting('brand.site_name', 'Examtube.in') }}">
    <meta name="twitter:description" content="{{ site_setting('brand.tagline', 'Practice smarter. Score higher.') }}">
    <meta name="twitter:image" content="{{ seo_default_image('organization') }}">
    @include('frontend.partials.theme-init')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/cookie-consent.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/icon-buttons.css') }}">
    @include('frontend.partials.integrations-head')
</head>
<body class="et-body et-auth-body">
    @include('frontend.partials.integrations-body')
    <script src="{{ versioned_asset('js/frontend/theme-boot.js') }}"></script>
    <a class="et-skip-link" href="#auth-main">Skip to content</a>
    <div class="et-auth">
        <aside class="et-auth__brand-panel">
            <a href="{{ route('home') }}" class="et-auth__logo">
                @if(is_file(public_path('images/brand/logo.svg')))
                    <img class="et-logo__img" src="{{ asset('images/brand/logo.svg') }}" alt="{{ site_setting('brand.site_name', 'Examtube.in') }}" width="150" height="32">
                @else
                    <span class="et-auth__logo-mark">{{ strtoupper(substr(site_setting('brand.logo_text', 'Examtube'), 0, 2)) }}</span>
                    <span>{{ site_setting('brand.logo_text', 'Examtube') }}<em>.in</em></span>
                @endif
            </a>
            <div class="et-auth__art" aria-hidden="true">
                <img src="{{ asset('frontend/images/login.svg') }}" alt="" loading="lazy" width="420" height="320">
            </div>
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

        <main id="auth-main" class="et-auth__main" tabindex="-1">
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
    <script src="{{ versioned_asset('js/frontend/recaptcha.js') }}" defer></script>
</body>
</html>
