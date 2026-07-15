@php
    $blogs = ($page['featuredBlogs'] ?? collect())->isNotEmpty()
        ? $page['featuredBlogs']
        : ($page['latestBlogs'] ?? collect());
@endphp
<section class="et-section et-section--alt">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
            'actionUrl' => Route::has('frontend.blogs.index') ? route('frontend.blogs.index') : null,
            'actionLabel' => ($section->settings['action_label'] ?? null) ?: 'All blogs',
        ])
        @if($blogs->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No blog posts yet', 'message' => ''])
        @else
            <div class="et-grid et-grid--3">
                @foreach($blogs as $blog)
                    @include('frontend.components.blog-card', ['blog' => $blog])
                @endforeach
            </div>
        @endif
    </div>
</section>
