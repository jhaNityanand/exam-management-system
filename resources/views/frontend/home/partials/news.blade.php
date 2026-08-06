@php $newsItems = $page['latestNews'] ?? collect(); @endphp
<section class="et-section" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => filled($section?->title) ? $section->title : 'Latest News',
            'subtitle' => filled($section?->subtitle) ? $section->subtitle : 'Stay updated with announcements and exam-world headlines.',
            'actionUrl' => route('frontend.news.index'),
            'actionLabel' => 'Show All News',
        ])
        @if($newsItems->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news yet', 'message' => ''])
        @else
            <div class="et-grid et-grid--3">
                @foreach($newsItems as $item)
                    @include('frontend.components.news-card', ['news' => $item])
                @endforeach
            </div>
            <div class="et-section__cta">
                <a href="{{ route('frontend.news.index') }}" class="et-btn et-btn--primary">Show All News</a>
            </div>
        @endif
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'home', 'position' => 'after_news'])
</section>
