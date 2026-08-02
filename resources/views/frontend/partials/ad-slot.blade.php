{{-- Dynamic advertisement slot container (rendered by AdvertisementService). --}}
<div
    class="et-ad et-ad--{{ $variant ?? 'inline' }}"
    data-ad-page="{{ $pageKey }}"
    data-ad-position="{{ $positionKey }}"
    role="complementary"
    aria-label="Advertisement"
>
    {!! $unitsHtml !!}
</div>
