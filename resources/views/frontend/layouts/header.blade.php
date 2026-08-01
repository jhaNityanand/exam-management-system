@php
    $brandName = 'Examtube.in';
    $logoSrc = asset('images/brand/logo.svg');
    $currentSlug = request()->routeIs('frontend.pages.show') ? request()->route('slug') : null;

    $navItems = [
        ['label' => 'Exams', 'url' => route('frontend.exams.index'), 'active' => request()->routeIs('frontend.exams.*')],
        ['label' => 'Blogs', 'url' => route('frontend.blogs.index'), 'active' => request()->routeIs('frontend.blogs.*')],
        ['label' => 'News', 'url' => route('frontend.news.index'), 'active' => request()->routeIs('frontend.news.*')],
        ['label' => 'Questions', 'url' => route('frontend.questions.index'), 'active' => request()->routeIs('frontend.questions.*')],
    ];

    $categoryItems = [
        ['label' => 'Show All', 'url' => route('frontend.categories.index')],
        ['label' => 'Blog Categories', 'url' => route('frontend.categories.index', ['type' => 'blogs'])],
        ['label' => 'News Categories', 'url' => route('frontend.categories.index', ['type' => 'news'])],
        ['label' => 'Exam Categories', 'url' => route('frontend.categories.index', ['type' => 'exams'])],
        ['label' => 'Question Categories', 'url' => route('frontend.questions.categories')],
    ];

    $moreItems = [
        ['label' => 'Authors', 'url' => route('frontend.authors.index')],
        ['label' => 'FAQs', 'url' => route('frontend.faqs.index')],
        ['label' => 'About Us', 'url' => url('/about-us')],
        ['label' => 'Contact Us', 'url' => url('/contact-us')],
        ['label' => 'Privacy Policy', 'url' => url('/privacy-policy')],
        ['label' => 'Terms & Conditions', 'url' => url('/terms-and-conditions')],
    ];

    $categoriesActive = request()->routeIs('frontend.categories.*')
        || request()->routeIs('frontend.questions.categories')
        || request()->routeIs('frontend.questions.category');

    $moreActive = request()->routeIs('frontend.authors.*')
        || request()->routeIs('frontend.faqs.*')
        || in_array($currentSlug, ['about-us', 'contact-us', 'privacy-policy', 'terms-and-conditions'], true);
@endphp
<header class="et-header" data-sticky-header>
    <div class="et-container et-header__bar">
        <a href="{{ route('home') }}" class="et-logo" aria-label="{{ $brandName }} home">
            <img class="et-logo__img" src="{{ $logoSrc }}" alt="{{ $brandName }}" width="160" height="34">
        </a>

        <nav class="et-nav" aria-label="Primary">
            @foreach($navItems as $item)
                <a href="{{ $item['url'] }}" class="et-nav__link {{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach

            <div class="et-nav__dropdown" data-nav-dropdown>
                <button
                    type="button"
                    class="et-nav__link et-nav__dropdown-trigger {{ $categoriesActive ? 'is-active' : '' }}"
                    data-nav-dropdown-trigger
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-controls="et-nav-categories"
                    id="et-nav-categories-btn"
                >
                    Categories
                    <svg class="et-nav__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div
                    class="et-nav__dropdown-panel"
                    id="et-nav-categories"
                    data-nav-dropdown-panel
                    role="menu"
                    aria-labelledby="et-nav-categories-btn"
                    hidden
                >
                    @foreach($categoryItems as $cat)
                        <a href="{{ $cat['url'] }}" class="et-nav__dropdown-item {{ $loop->first ? 'et-nav__dropdown-item--emphasis' : '' }}" role="menuitem">
                            <span class="et-nav__dropdown-label">{{ $cat['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="et-nav__dropdown" data-nav-dropdown>
                <button
                    type="button"
                    class="et-nav__link et-nav__dropdown-trigger {{ $moreActive ? 'is-active' : '' }}"
                    data-nav-dropdown-trigger
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-controls="et-nav-more"
                    id="et-nav-more-btn"
                >
                    More
                    <svg class="et-nav__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div
                    class="et-nav__dropdown-panel"
                    id="et-nav-more"
                    data-nav-dropdown-panel
                    role="menu"
                    aria-labelledby="et-nav-more-btn"
                    hidden
                >
                    @foreach($moreItems as $item)
                        <a href="{{ $item['url'] }}" class="et-nav__dropdown-item" role="menuitem">
                            <span class="et-nav__dropdown-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>

        <div class="et-header__actions">
            <button type="button" class="et-icon-btn" data-search-open aria-label="Open search" aria-expanded="false" aria-controls="et-search-dialog">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            </button>

            <button type="button" class="et-icon-btn et-theme-toggle" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
                <svg class="et-theme-icon et-theme-icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 1110.5 3 7 7 0 0021 14.5z"/></svg>
                <svg class="et-theme-icon et-theme-icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v2M12 19v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M3 12h2M19 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            @auth
                @php($headerAvatar = user_avatar(auth()->user()))
                <a
                    href="{{ route('frontend.account.dashboard') }}"
                    class="et-profile-link"
                    aria-label="Go to dashboard"
                    title="Dashboard"
                >
                    <span class="et-profile__avatar" style="--ua-bg: {{ $headerAvatar['color'] }}">
                        @if($headerAvatar['url'])
                            <img src="{{ $headerAvatar['url'] }}" alt="">
                        @else
                            {{ $headerAvatar['initials'] }}
                        @endif
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="et-header__logout-form">
                    @csrf
                    <button type="submit" class="et-icon-btn et-logout-btn et-header__auth-btn" aria-label="Logout" title="Logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="et-btn et-btn--ghost et-btn--sm et-header__auth-btn">Login</a>
                <a href="{{ route('register') }}" class="et-btn et-btn--primary et-btn--sm et-header__auth-btn">Register</a>
            @endauth

            <button
                type="button"
                class="et-icon-btn et-mobile-toggle"
                data-mobile-nav-toggle
                aria-expanded="false"
                aria-controls="et-mobile-drawer"
                aria-label="Open menu"
            >
                <svg class="et-mobile-toggle__open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg class="et-mobile-toggle__close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>
</header>

<div class="et-drawer-backdrop" data-mobile-nav-backdrop hidden></div>

<aside
    id="et-mobile-drawer"
    class="et-drawer"
    data-mobile-nav
    role="dialog"
    aria-modal="true"
    aria-label="Navigation menu"
    hidden
>
    <div class="et-drawer__head">
        <a href="{{ route('home') }}" class="et-logo" aria-label="{{ $brandName }}">
            <img class="et-logo__img" src="{{ $logoSrc }}" alt="{{ $brandName }}" width="140" height="30">
        </a>
        <button type="button" class="et-icon-btn" data-mobile-nav-close aria-label="Close menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <nav class="et-drawer__nav" aria-label="Mobile">
        @foreach($navItems as $item)
            <a href="{{ $item['url'] }}" class="et-drawer__link {{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
        @endforeach

        <div class="et-drawer__group" data-drawer-accordion>
            <button type="button" class="et-drawer__link et-drawer__accordion-trigger {{ $categoriesActive ? 'is-active' : '' }}" data-drawer-accordion-trigger aria-expanded="false" aria-controls="et-drawer-categories" id="et-drawer-categories-trigger">
                Categories
                <svg class="et-nav__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div class="et-drawer__submenu" id="et-drawer-categories" role="region" aria-labelledby="et-drawer-categories-trigger" data-drawer-accordion-panel hidden>
                @foreach($categoryItems as $cat)
                    <a href="{{ $cat['url'] }}" class="et-drawer__sublink">{{ $cat['label'] }}</a>
                @endforeach
            </div>
        </div>

        <div class="et-drawer__group" data-drawer-accordion>
            <button type="button" class="et-drawer__link et-drawer__accordion-trigger {{ $moreActive ? 'is-active' : '' }}" data-drawer-accordion-trigger aria-expanded="false" aria-controls="et-drawer-more" id="et-drawer-more-trigger">
                More
                <svg class="et-nav__chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div class="et-drawer__submenu" id="et-drawer-more" role="region" aria-labelledby="et-drawer-more-trigger" data-drawer-accordion-panel hidden>
                @foreach($moreItems as $item)
                    <a href="{{ $item['url'] }}" class="et-drawer__sublink">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </div>
    </nav>

    <div class="et-drawer__auth">
        @auth
            <a href="{{ route('frontend.account.dashboard') }}" class="et-btn et-btn--primary et-btn--block">Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="et-btn et-btn--logout et-btn--block">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <path d="M16 17l5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                    Logout
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="et-btn et-btn--ghost et-btn--block">Login</a>
            <a href="{{ route('register') }}" class="et-btn et-btn--primary et-btn--block">Register</a>
        @endauth
    </div>
</aside>
