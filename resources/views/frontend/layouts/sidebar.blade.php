@php
    $user = $user ?? auth()->user();
    $user?->loadMissing(['profile', 'organizations']);
    $avatarUrl = $avatarUrl ?? null;
    $membership = $user?->activeOrganizationRole() ?: 'candidate';
    $membershipLabel = ucwords(str_replace('_', ' ', (string) $membership));
    $nameParts = preg_split('/\s+/', trim((string) ($user->name ?? 'User'))) ?: ['U'];
    $initials = count($nameParts) >= 2
        ? strtoupper(mb_substr($nameParts[0], 0, 1).mb_substr($nameParts[1], 0, 1))
        : strtoupper(mb_substr((string) ($user->name ?? 'U'), 0, 2));
@endphp

<aside class="ca-sidebar" id="ca-sidebar" aria-label="Account navigation">
    <div class="ca-sidebar__user">
        <div class="ca-sidebar__avatar" aria-hidden="true">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="">
            @else
                <span>{{ $initials }}</span>
            @endif
        </div>
        <div class="ca-sidebar__identity">
            <strong>{{ $user->name ?? 'Learner' }}</strong>
            <span>{{ $user->email ?? '' }}</span>
            <em>{{ $membershipLabel }}</em>
            <small>Joined {{ optional($user->created_at)->format('M Y') ?: '—' }}</small>
        </div>
    </div>

    <nav class="ca-sidebar__nav">
        <a href="{{ route('frontend.account.dashboard') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.dashboard') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('frontend.account.exams') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.exams') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4h10a2 2 0 0 1 2 2v14l-7-3-7 3V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>My Exams</span>
        </a>
        <a href="{{ route('frontend.account.results') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.results') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5M4 19h16M8 15l3-3 2 2 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Results</span>
        </a>
        <a href="{{ route('frontend.account.profile') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.profile*') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4 0-7 2-7 4v1h14v-1c0-2-3-4-7-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            <span>Profile</span>
        </a>
        <a href="{{ route('frontend.account.settings') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.settings*') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 13a7.7 7.7 0 0 0 .1-2l2-1.2-2-3.4-2.3.7a7.6 7.6 0 0 0-1.7-1L15 4h-6l-.5 2.1a7.6 7.6 0 0 0-1.7 1L4.5 6.4l-2 3.4L4.5 11a7.7 7.7 0 0 0 0 2l-2 1.2 2 3.4 2.3-.7a7.6 7.6 0 0 0 1.7 1L9 20h6l.5-2.1a7.6 7.6 0 0 0 1.7-1l2.3.7 2-3.4-2.1-1.2Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
            <span>Settings</span>
        </a>
        <a href="{{ route('frontend.account.invoices') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.invoices') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16l-3-1.5L13 21l-3-1.5L7 21V5a2 2 0 0 1 2-2Zm3 5h4M10 12h4M10 16h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Invoices</span>
        </a>
        <a href="{{ route('frontend.account.activity') }}" class="ca-nav__link {{ request()->routeIs('frontend.account.activity') ? 'is-active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 12h10M4 19h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="18" cy="12" r="2" stroke="currentColor" stroke-width="1.8"/><circle cx="15" cy="19" r="2" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>Activity Tracking</span>
        </a>
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="ca-sidebar__logout">
        @csrf
        <button type="submit" class="ca-nav__link ca-nav__link--danger">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2M15 12H3m0 0 3-3M3 12l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Logout</span>
        </button>
    </form>
</aside>
