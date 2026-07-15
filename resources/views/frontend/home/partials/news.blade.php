@php
    $newsItems = ($page['breakingNews'] ?? collect())->isNotEmpty()
        ? $page['breakingNews']
        : (($page['trendingNews'] ?? collect())->isNotEmpty()
            ? $page['trendingNews']
            : ($page['latestNews'] ?? collect()));
@endphp
<section class="et-section">
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section->title ?? '',
            'subtitle' => $section->subtitle ?? '',
            'actionUrl' => Route::has('frontend.news.index') ? route('frontend.news.index') : null,
            'actionLabel' => ($section->settings['action_label'] ?? null) ?: 'All news',
        ])
        @if($newsItems->isEmpty())
            @include('frontend.partials.empty-state', ['title' => 'No news yet', 'message' => ''])
        @else
            <div class="et-grid et-grid--3">
                @foreach($newsItems as $news)
                    @include('frontend.components.news-card', ['news' => $news])
                @endforeach
            </div>
        @endif
    </div>
</section>
