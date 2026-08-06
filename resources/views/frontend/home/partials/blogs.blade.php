@php
    $blogs = $page['featuredBlogs'] ?? ($page['latestBlogs'] ?? collect());
@endphp
<section class="et-section et-section--alt" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => filled($section?->title) ? $section->title : 'From the Blog',
            'subtitle' => filled($section?->subtitle) ? $section->subtitle : 'Guides and insights to keep your preparation on track.',
            'actionUrl' => route('frontend.blogs.index'),
            'actionLabel' => 'Show All Blogs',
        ])
        @if($blogs->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No blog posts yet', 'message' => ''])
        @else
            <div class="et-grid et-grid--3">
                @foreach($blogs as $blog)
                    @include('frontend.components.blog-card', ['blog' => $blog])
                @endforeach
            </div>
            <div class="et-section__cta">
                <a href="{{ route('frontend.blogs.index') }}" class="et-btn et-btn--primary">Show All Blogs</a>
            </div>
        @endif
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'home', 'position' => 'after_blogs'])
</section>
