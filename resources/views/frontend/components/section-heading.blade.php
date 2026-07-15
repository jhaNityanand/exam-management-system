@php
    $headingTag = $tag ?? 'h2';
@endphp
<div class="et-section-heading">
    <div class="et-section-heading__copy">
        @if(!empty($eyebrow))
            <span class="et-section-heading__eyebrow">{{ $eyebrow }}</span>
        @endif
        @if(!empty($title))
            <{{ $headingTag }} class="et-section-heading__title">{{ $title }}</{{ $headingTag }}>
        @endif
        @if(!empty($subtitle))
            <p class="et-section-heading__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}" class="et-btn et-btn--ghost et-btn--sm">{{ $actionLabel }}</a>
    @endif
</div>
