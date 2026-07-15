@php
    $catUrl = Route::has('frontend.categories.show')
        ? route('frontend.categories.show', $category->slug ?? $category)
        : '#';
    $initial = strtoupper(mb_substr($category->name ?? 'C', 0, 1));
@endphp
<a href="{{ $catUrl }}" class="et-category-card">
    <div class="et-category-card__icon" aria-hidden="true">{{ $initial }}</div>
    <h3>{{ $category->name }}</h3>
    @if($category->description)
        <p>{{ \Illuminate\Support\Str::limit(strip_tags($category->description), 90) }}</p>
    @endif
</a>
