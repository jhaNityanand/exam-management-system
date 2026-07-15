@php $categories = $page['categories'] ?? collect(); @endphp
<section class="et-section">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
            'actionUrl' => Route::has('frontend.categories.index') ? route('frontend.categories.index') : null,
            'actionLabel' => ($section->settings['action_label'] ?? null) ?: 'All categories',
        ])
        @if($categories->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'Categories coming soon', 'message' => ''])
        @else
            <div class="et-grid et-grid--4">
                @foreach($categories as $category)
                    @include('frontend.components.category-card', ['category' => $category])
                @endforeach
            </div>
        @endif
    </div>
</section>
