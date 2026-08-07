{{-- Dynamic advertisement slot container (rendered by AdvertisementService). --}}
@php
    $isPreview = ! empty($isPreview);
    $previewLabel = $previewLabel ?? 'Ads';
    $previewSource = $previewSource ?? 'mixed';
@endphp
<div
    @class([
        'et-ad',
        'et-ad--'.($variant ?? 'inline'),
        'et-ad--preview' => $isPreview,
        'et-ad--preview-'.$previewSource => $isPreview,
    ])
    data-ad-page="{{ $pageKey }}"
    data-ad-position="{{ $positionKey }}"
    @if($isPreview) data-ad-preview="1" data-ad-label="{{ $previewLabel }}" @endif
    role="complementary"
    aria-label="{{ $isPreview ? $previewLabel : 'Advertisement' }}"
>
    @if($isPreview)
        <span class="et-ad__preview-label" aria-hidden="true">{{ $previewLabel }}</span>
    @endif
    {!! $unitsHtml !!}
</div>
