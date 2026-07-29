@php
    $code = $code ?? '404';
    $title = $title ?? 'Page not found';
    $message = $message ?? 'The page you are looking for does not exist or may have moved.';
    $brandName = $siteBrand['name'] ?? ($siteSettings['site_name'] ?? config('app.name', 'Examtube.in'));
@endphp
<section class="et-error">
    <div class="et-container et-error__inner">
        <img class="et-error__art" src="{{ asset('frontend/images/error.svg') }}" alt="" loading="eager">
        <p class="et-badge et-badge--soft">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p class="et-error__msg">{{ $message }}</p>
        <div class="et-error__actions">
            <a href="{{ route('home') }}" class="et-btn et-btn--primary">Back to Home</a>
            @if(Route::has('frontend.search'))
                <button type="button" class="et-btn et-btn--ghost" data-search-open>Search</button>
            @endif
        </div>
        <p class="et-error__brand">{{ $brandName }}</p>
    </div>
</section>
