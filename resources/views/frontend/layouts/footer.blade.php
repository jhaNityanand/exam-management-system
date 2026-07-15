@php
    $brandName = $siteBrand['name'] ?? ($siteSettings['site_name'] ?? ($siteSettings['brand.site_name'] ?? config('app.name', 'Examtube.in')));
    $logoText = $siteBrand['logo_text'] ?? ($siteSettings['logo_text'] ?? ($siteSettings['brand.logo_text'] ?? 'Examtube'));
    $about = $siteSettings['footer.about'] ?? ($siteSettings['about'] ?? '');
    $copyright = $siteSettings['footer.copyright'] ?? ($siteSettings['copyright'] ?? '© {year} '.$brandName);
    $copyright = str_replace('{year}', (string) now()->year, $copyright);
    $newsTitle = $siteSettings['newsletter.title'] ?? ($siteSettings['title'] ?? '');
    $newsSubtitle = $siteSettings['newsletter.subtitle'] ?? '';
    $newsCta = $siteSettings['newsletter.cta'] ?? 'Subscribe';
@endphp
<footer class="et-footer">
    <div class="et-container et-footer__grid">
        <div class="et-footer__about">
            <a href="{{ route('home') }}" class="et-logo">
                <span class="et-logo__mark">{{ strtoupper(mb_substr($logoText, 0, 1)) }}</span>
                <span class="et-logo__text">{{ $logoText }}</span>
            </a>
            @if($about !== '')
                <p>{{ $about }}</p>
            @endif
            <div class="et-social" aria-label="Social links">
                @foreach(($socialLinks ?? collect()) as $link)
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" title="{{ $link->label }}">
                        {{ strtoupper(mb_substr($link->platform ?? $link->label, 0, 2)) }}
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <h3>Explore</h3>
            <div class="et-footer__links">
                @foreach(($footerMenu ?? collect()) as $item)
                    <a href="{{ $item->href() }}" @if(($item->target ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                @endforeach
            </div>
        </div>

        <div>
            <h3>Legal</h3>
            <div class="et-footer__links">
                @foreach(($footerLegalMenu ?? collect()) as $item)
                    <a href="{{ $item->href() }}" @if(($item->target ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                @endforeach
            </div>
        </div>

        <div>
            <h3>{{ $newsTitle !== '' ? $newsTitle : 'Newsletter' }}</h3>
            @if($newsSubtitle !== '')
                <p style="color:var(--et-text-muted);font-size:.9rem;margin:0 0 .85rem">{{ $newsSubtitle }}</p>
            @endif
            @include('frontend.partials.newsletter-form', [
                'cta' => $newsCta,
                'compact' => true,
            ])
        </div>
    </div>

    <div class="et-container et-footer__bottom">
        <div>{{ $copyright }}</div>
        <div class="et-footer__legal">
            @foreach(($footerLegalMenu ?? collect())->take(3) as $item)
                <a href="{{ $item->href() }}">{{ $item->label }}</a>
            @endforeach
        </div>
    </div>
</footer>
