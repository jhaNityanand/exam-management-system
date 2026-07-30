@php
    $type = $item->type ?? 'exams';
    $typeLabel = $item->type_label ?? ucfirst($type);
    $initial = strtoupper(mb_substr($item->name ?? 'C', 0, 1));
@endphp
<a href="{{ $item->url }}" class="et-category-card" data-category-type="{{ $type }}">
    <div class="et-category-card__top">
        <div class="et-category-card__icon" aria-hidden="true">{{ $initial }}</div>
        <span class="et-badge et-badge--soft">{{ $typeLabel }}</span>
    </div>
    <h3>{{ $item->name }}</h3>
    @if(! empty($item->description))
        <p>{{ \Illuminate\Support\Str::limit(strip_tags($item->description), 90) }}</p>
    @endif
</a>
