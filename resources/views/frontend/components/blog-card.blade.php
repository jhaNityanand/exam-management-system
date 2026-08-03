@php
    $blogUrl = Route::has('frontend.blogs.show')
        ? route('frontend.blogs.show', $blog->slug)
        : '#';
    $images = method_exists($blog, 'bannerUrls')
        ? $blog->bannerUrls()
        : array_values(array_filter([
            $blog->bannerImage->file_url ?? null,
        ]));
    $author = $blog->author_name ?: ($blog->author->name ?? null);
@endphp
<article class="et-card et-blog-card">
    @include('frontend.partials.card-media', [
        'images' => $images,
        'href' => $blogUrl,
        'alt' => $blog->title,
    ])
    <div class="et-card__body">
        @if($blog->category || $blog->published_at)
            <div class="et-card__meta et-card__meta--split">
                <span class="et-card__meta-start">
                    @if($blog->category)
                        <span class="et-badge">{{ $blog->category->name }}</span>
                    @endif
                </span>
                @if($blog->published_at)
                    <span class="et-card__meta-end">{{ $blog->published_at->format('d M Y') }}</span>
                @endif
            </div>
        @endif
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
