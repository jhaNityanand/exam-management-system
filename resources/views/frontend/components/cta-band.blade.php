@php
    $cta = $cta ?? [];
    $title = $title ?? ($cta['title'] ?? '');
    $subtitle = $subtitle ?? ($cta['subtitle'] ?? '');
    $primaryLabel = $primaryLabel ?? ($cta['primary_label'] ?? null);
    $primaryUrl = $primaryUrl ?? ($cta['primary_url'] ?? null);
    $secondaryLabel = $secondaryLabel ?? ($cta['secondary_label'] ?? null);
    $secondaryUrl = $secondaryUrl ?? ($cta['secondary_url'] ?? null);
@endphp
<section class="et-cta-band">
    @if($title !== '')
        <h2>{{ $title }}</h2>
    @endif
    @if($subtitle !== '')
        <p>{{ $subtitle }}</p>
    @endif
    <div class="et-cta-band__actions">
        @if($primaryLabel && $primaryUrl)
            <a href="{{ $primaryUrl }}" class="et-btn et-btn--primary">{{ $primaryLabel }}</a>
        @endif
        @if($secondaryLabel && $secondaryUrl)
            <a href="{{ $secondaryUrl }}" class="et-btn et-btn--ghost">{{ $secondaryLabel }}</a>
        @endif
    </div>
</section>
