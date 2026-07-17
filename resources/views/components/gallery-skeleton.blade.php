@props([
    'count' => 12,
])

@for ($i = 0; $i < $count; $i++)
    <div class="gallery-skeleton-card" aria-hidden="true">
        <div class="gallery-skeleton-card__thumb"></div>
        <div class="gallery-skeleton-card__line"></div>
        <div class="gallery-skeleton-card__line" style="width: {{ 45 + ($i % 4) * 10 }}%"></div>
    </div>
@endfor
