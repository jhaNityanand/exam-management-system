@php
    $type = $item->type ?? 'exams';
    $typeLabel = $item->type_label ?? ucfirst($type);
    $initial = strtoupper(mb_substr($item->name ?? 'C', 0, 1));
    $description = ! empty($item->description)
        ? \Illuminate\Support\Str::limit(strip_tags($item->description), 100)
        : null;
@endphp
<a
    href="{{ $item->url }}"
    class="et-catalog-card et-catalog-card--{{ $type }}"
    data-category-type="{{ $type }}"
>
    <div class="et-catalog-card__glow" aria-hidden="true"></div>

    <div class="et-catalog-card__top">
        <span class="et-catalog-card__icon" aria-hidden="true">{{ $initial }}</span>
        <span class="et-catalog-card__type">{{ $typeLabel }}</span>
    </div>

    <h3 class="et-catalog-card__title">{{ $item->name }}</h3>

    @if($description)
        <p class="et-catalog-card__desc">{{ $description }}</p>
    @else
        <p class="et-catalog-card__desc et-catalog-card__desc--muted">Explore topics in this category.</p>
    @endif

    <span class="et-catalog-card__cta">
        Browse
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
</a>
