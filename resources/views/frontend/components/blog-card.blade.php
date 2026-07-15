@php
    $blogUrl = Route::has('frontend.blogs.show')
        ? route('frontend.blogs.show', $blog->slug)
        : '#';
    $banner = method_exists($blog, 'bannerUrl') ? $blog->bannerUrl() : ($blog->bannerImage->file_url ?? null);
    $words = str_word_count(strip_tags((string) ($blog->content ?? '')));
    $readingMins = max(1, (int) ceil($words / 200));
    $author = $blog->author_name ?: ($blog->author->name ?? null);
@endphp
<article class="et-card et-blog-card">
    <a href="{{ $blogUrl }}" class="et-card__media" tabindex="-1" aria-hidden="true">
        @if($banner)
            <img src="{{ $banner }}" alt="" loading="lazy">
        @endif
    </a>
    <div class="et-card__body">
        <div class="et-card__meta">
            @if($blog->category)
                <span class="et-badge">{{ $blog->category->name }}</span>
            @endif
            <span>{{ $readingMins }} min read</span>
            @if($blog->published_at)
                <span>{{ $blog->published_at->format('d M Y') }}</span>
            @endif
        </div>
        <h3 class="et-card__title"><a href="{{ $blogUrl }}">{{ $blog->title }}</a></h3>
        @if($blog->excerpt)
            <p class="et-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($blog->excerpt), 140) }}</p>
        @endif
        <div class="et-card__footer">
            <span class="et-card__meta">{{ $author ?: 'Examtube Editorial' }}</span>
            <a href="{{ $blogUrl }}" class="et-btn et-btn--soft et-btn--sm">Read</a>
        </div>
    </div>
</article>
