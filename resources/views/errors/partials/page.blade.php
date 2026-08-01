@php
    $code = (string) ($code ?? '404');
    $title = $title ?? 'Something went wrong';
    $message = $message ?? 'Please try again or return to the homepage.';
    $showHome = $showHome ?? true;
    $showSearch = $showSearch ?? false;
    $showLogin = $showLogin ?? false;
    $showAccount = $showAccount ?? false;
    $showRefresh = $showRefresh ?? false;
    $brandName = $siteBrand['name'] ?? ($siteSettings['site_name'] ?? config('app.name', 'Examtube.in'));
    $tone = match ($code) {
        '403' => 'rose',
        '404' => 'teal',
        '419' => 'amber',
        '429' => 'orange',
        '500' => 'rose',
        '503' => 'slate',
        default => 'teal',
    };
@endphp

<section class="et-err et-err--{{ $tone }}" aria-labelledby="et-err-title">
    <div class="et-container et-err__inner">
        <div class="et-err__art" aria-hidden="true">
            @include('errors.partials.illustration', ['code' => $code])
        </div>

        <p class="et-err__badge">{{ $code }}</p>
        <h1 id="et-err-title" class="et-err__title">{{ $title }}</h1>
        <p class="et-err__message">{{ $message }}</p>

        <div class="et-err__actions">
            @if($showHome)
                <a href="{{ route('home') }}" class="et-btn et-btn--primary">
                    <svg class="et-err__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/>
                    </svg>
                    Home
                </a>
            @endif

            @if($showSearch && Route::has('frontend.search'))
                <button type="button" class="et-btn et-btn--ghost" data-search-open>
                    <svg class="et-err__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                    </svg>
                    Search
                </button>
            @endif

            @if($showRefresh)
                <button type="button" class="et-btn et-btn--ghost" onclick="window.location.reload()">
                    <svg class="et-err__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5 13a7 7 0 0012.9 3M19 11A7 7 0 006.1 8"/>
                    </svg>
                    Refresh
                </button>
            @endif

            @if($showLogin)
                <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="et-btn et-btn--ghost">
                    <svg class="et-err__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                    </svg>
                    Login
                </a>
            @elseif($showAccount && Route::has('frontend.account.dashboard'))
                <a href="{{ route('frontend.account.dashboard') }}" class="et-btn et-btn--ghost">
                    <svg class="et-err__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    My account
                </a>
            @endif
        </div>

        <p class="et-err__brand">{{ $brandName }}</p>
    </div>
</section>
