<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('frontend.partials.seo')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ versioned_asset('css/frontend/app.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/icon-buttons.css') }}">
    @stack('styles')
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
<body class="et-body">
    <a class="et-skip-link" href="#main-content">Skip to content</a>
    @include('frontend.partials.announcement-bar')
    @include('frontend.layouts.header')

    <main id="main-content" class="et-main" tabindex="-1">
        @yield('content')
    </main>

    @include('frontend.layouts.footer')

    {{-- Global search overlay --}}
    <div
        class="et-search-overlay"
        data-search-overlay
        data-suggest-url="{{ Route::has('frontend.search.suggest') ? route('frontend.search.suggest') : '#' }}"
    >
        <div class="et-search-panel" id="et-search-dialog" role="dialog" aria-modal="true" aria-label="Search">
            <form class="et-search-panel__form" action="{{ Route::has('frontend.search') ? route('frontend.search') : '#' }}" method="get">
                <input
                    class="et-search-panel__input"
                    type="search"
                    name="q"
                    placeholder="Search exams, blogs, news, questions…"
                    autocomplete="off"
                    data-search-input
                    aria-label="Search Examtube"
                >
                <button type="submit" class="et-btn et-btn--primary et-btn--sm">Search</button>
                <button type="button" class="et-icon-btn" data-search-close aria-label="Close search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </form>
            <div class="et-search-suggest" data-search-suggest>
                <div class="et-search-suggest__empty">Start typing to see suggestions</div>
            </div>
        </div>
    </div>

    <div class="et-visually-hidden" role="status" aria-live="polite" data-global-live></div>

    <script src="{{ versioned_asset('js/frontend/app.js') }}" defer></script>
    <script src="{{ versioned_asset('js/frontend/load-more.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
