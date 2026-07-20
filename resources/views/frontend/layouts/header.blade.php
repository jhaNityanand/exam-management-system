@php
    $brandName = $siteBrand['name'] ?? ($siteSettings['site_name'] ?? ($siteSettings['brand.site_name'] ?? config('app.name', 'Examtube.in')));
    $logoText = $siteBrand['logo_text'] ?? ($siteSettings['logo_text'] ?? ($siteSettings['brand.logo_text'] ?? 'Examtube'));
    $logoPath = public_path('images/brand/examtube-logo.svg');
    $hasLogoFile = is_file($logoPath);
@endphp
<header class="et-header" data-sticky-header>
    <div class="et-container et-header__bar">
        <a href="{{ route('home') }}" class="et-logo" aria-label="{{ $brandName }}">
            @if($hasLogoFile)
                <img class="et-logo__img" src="{{ asset('images/brand/examtube-logo.svg') }}" alt="{{ $brandName }}" width="160" height="34">
            @else
                <span class="et-logo__mark">{{ strtoupper(mb_substr(preg_replace('/\s+/', '', $logoText) ?: 'E', 0, 1)) }}</span>
                <span class="et-logo__text">
                    @if(\Illuminate\Support\Str::endsWith(strtolower($logoText), '.in'))
                        {{ $logoText }}
                    @else
                        {{ $logoText }}<span>.in</span>
                    @endif
                </span>
            @endif
        </a>

        <nav class="et-nav" aria-label="Primary">
            @foreach(($headerMenu ?? collect()) as $item)
                <a
                    href="{{ $item->href() }}"
                    class="et-nav__link {{ request()->url() === rtrim($item->href(), '/') || request()->url() === $item->href() ? 'is-active' : '' }}"
                    @if(($item->target ?? '_self') === '_blank') target="_blank" rel="noopener" @endif
                >{{ $item->label }}</a>
            @endforeach
            @if(Route::has('frontend.questions.index'))
                <a href="{{ route('frontend.questions.index') }}" class="et-nav__link {{ request()->routeIs('frontend.questions.*') ? 'is-active' : '' }}">Questions</a>
            @endif
        </nav>

        <div class="et-header__actions">
            <button type="button" class="et-icon-btn" data-search-open aria-label="Open search" aria-expanded="false" aria-controls="et-search-dialog">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            </button>

            <button type="button" class="et-icon-btn" data-theme-toggle aria-label="Toggle theme">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v2M12 19v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M3 12h2M19 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            @auth
                <div class="et-profile" data-profile-menu>
                    <button type="button" class="et-profile__btn" data-profile-toggle aria-haspopup="true" aria-expanded="false">
                        <span class="et-profile__avatar">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        <span class="et-visually-hidden">Account menu</span>
                    </button>
                    <div class="et-profile__menu" role="menu">
                        @if(Route::has('frontend.account.dashboard'))
                            <a href="{{ route('frontend.account.dashboard') }}">Dashboard</a>
                        @endif
                        @if(Route::has('frontend.account.exams'))
                            <a href="{{ route('frontend.account.exams') }}">My exams</a>
                        @endif
                        @if(Route::has('frontend.account.settings'))
                            <a href="{{ route('frontend.account.settings') }}">Settings</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">Log out</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="et-btn et-btn--ghost et-btn--sm et-header__auth-btn">Login</a>
                <a href="{{ route('register') }}" class="et-btn et-btn--primary et-btn--sm et-header__auth-btn">Register</a>
            @endauth

            <button type="button" class="et-icon-btn et-mobile-toggle" data-mobile-nav-toggle aria-expanded="false" aria-label="Open menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <div class="et-container et-mobile-nav" data-mobile-nav>
        @foreach(($headerMenu ?? collect()) as $item)
            <a href="{{ $item->href() }}" @if(($item->target ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
        @endforeach
        @if(Route::has('frontend.questions.index'))
            <a href="{{ route('frontend.questions.index') }}">Questions</a>
        @endif
        @guest
            <div class="et-mobile-nav__auth">
                <a href="{{ route('login') }}" class="et-btn et-btn--ghost et-btn--sm">Login</a>
                <a href="{{ route('register') }}" class="et-btn et-btn--primary et-btn--sm">Register</a>
            </div>
        @endguest
    </div>
</header>
