@php
    $banners = $banners ?? collect();
@endphp
<section class="et-hero" data-hero-slider>
    <div class="et-hero__slider">
        @forelse($banners as $i => $banner)
            @php
                $desktop = $banner->image->file_url ?? null;
                $mobile = $banner->mobileImage->file_url ?? $desktop;
            @endphp
            <div class="et-hero__slide {{ $i === 0 ? 'is-active' : '' }}" data-hero-slide>
                <div class="et-container et-hero__grid">
                    <div>
                        @if($banner->badge_text)
                            <span class="et-hero__badge">{{ $banner->badge_text }}</span>
                        @endif
                        <h1 class="et-hero__title">{{ $banner->title }}</h1>
                        @if($banner->subtitle)
                            <p class="et-hero__subtitle">{{ $banner->subtitle }}</p>
                        @endif
                        @if($banner->description)
                            <p class="et-hero__desc">{{ $banner->description }}</p>
                        @endif
                        <div class="et-hero__actions">
                            @if($banner->primary_cta_label && $banner->primary_cta_url)
                                <a href="{{ $banner->primary_cta_url }}" class="et-btn et-btn--primary">{{ $banner->primary_cta_label }}</a>
                            @endif
                            @if($banner->secondary_cta_label && $banner->secondary_cta_url)
                                <a href="{{ $banner->secondary_cta_url }}" class="et-btn et-btn--ghost">{{ $banner->secondary_cta_label }}</a>
                            @endif
                        </div>
                        @if($banner->show_search)
                            <form class="et-hero__search" action="{{ Route::has('frontend.search') ? route('frontend.search') : '#' }}" method="get">
                                <input type="search" name="q" placeholder="Search exams, topics, news…" aria-label="Search">
                                <button type="submit" class="et-btn et-btn--primary et-btn--sm">Search</button>
                            </form>
                        @endif
                    </div>
                    <div class="et-hero__media">
                        @if($desktop || $mobile)
                            <picture>
                                @if($mobile)
                                    <source media="(max-width: 767px)" srcset="{{ $mobile }}">
                                @endif
                                @if($desktop)
                                    <img src="{{ $desktop }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                                @elseif($mobile)
                                    <img src="{{ $mobile }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                                @endif
                            </picture>
                        @else
                            <div class="et-hero__media-fallback">
                                Structured mocks · Instant insights · Daily news
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="et-hero__slide is-active" data-hero-slide>
                <div class="et-container et-hero__grid">
                    <div>
                        <h1 class="et-hero__title">{{ $siteSettings['site_name'] ?? 'Examtube.in' }}</h1>
                        <p class="et-hero__desc">{{ $siteSettings['tagline'] ?? ($siteSettings['brand.tagline'] ?? '') }}</p>
                        <div class="et-hero__actions">
                            @if(Route::has('frontend.exams.index'))
                                <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary">Browse exams</a>
                            @endif
                        </div>
                    </div>
                    <div class="et-hero__media">
                        <div class="et-hero__media-fallback">Practice smarter. Score higher.</div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
    @if($banners->count() > 1)
        <div class="et-hero__dots" role="tablist" aria-label="Hero slides">
            @foreach($banners as $i => $banner)
                <button
                    type="button"
                    class="et-hero__dot {{ $i === 0 ? 'is-active' : '' }}"
                    data-hero-dot
                    aria-label="Go to slide {{ $i + 1 }}"
                    aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                ></button>
            @endforeach
        </div>
    @endif
</section>
