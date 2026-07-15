<aside class="et-sidebar" aria-label="Account">
    @if(Route::has('frontend.account.dashboard'))
        <a href="{{ route('frontend.account.dashboard') }}" class="{{ request()->routeIs('frontend.account.dashboard') ? 'is-active' : '' }}">Dashboard</a>
    @endif
    @if(Route::has('frontend.account.exams'))
        <a href="{{ route('frontend.account.exams') }}" class="{{ request()->routeIs('frontend.account.exams') ? 'is-active' : '' }}">My exams</a>
    @endif
    @if(Route::has('frontend.account.results'))
        <a href="{{ route('frontend.account.results') }}" class="{{ request()->routeIs('frontend.account.results') ? 'is-active' : '' }}">Results</a>
    @endif
    @if(Route::has('frontend.account.settings'))
        <a href="{{ route('frontend.account.settings') }}" class="{{ request()->routeIs('frontend.account.settings') ? 'is-active' : '' }}">Settings</a>
    @endif
</aside>
