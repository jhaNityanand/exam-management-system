{{-- Question category card --}}
<a class="et-card et-category-card" href="{{ route('frontend.questions.category', $category->slug) }}">
    <div class="et-card__body">
        <div class="et-category-card__icon" aria-hidden="true">
            @if(!empty($category->image_path))
                <img src="{{ asset($category->image_path) }}" alt="" loading="lazy" width="48" height="48">
            @else
                <span>{{ strtoupper(mb_substr($category->name, 0, 1)) }}</span>
            @endif
        </div>
        <h3 class="et-card__title">{{ $category->name }}</h3>
        @if($category->description)
            <p class="et-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 110) }}</p>
        @endif
        <p class="et-card__meta">{{ (int) ($category->questions_count ?? 0) }} questions</p>
    </div>
</a>
