{{--
  Article detail right rail: author, tags, related categories, latest posts.
  Expected: $articleAside (from ArticleAsideService)
--}}
@php
    $aside = $articleAside ?? null;
    $author = $aside['author'] ?? null;
    $tags = collect($aside['tags'] ?? []);
    $categories = collect($aside['categories'] ?? []);
    $latest = collect($aside['latest'] ?? []);
    $type = $aside['type'] ?? 'blog';
    $latestTitle = $type === 'news' ? 'Latest news' : 'Latest blogs';
    $viewAllUrl = $type === 'news' ? route('frontend.news.index') : route('frontend.blogs.index');
    $viewAllLabel = $type === 'news' ? 'View all' : 'View all';
    $articleMeta = collect($articleHeaderMeta ?? []);
@endphp

@if($aside || $articleMeta->isNotEmpty())
    <aside class="et-article-aside" aria-label="Article sidebar">
        @if($articleMeta->isNotEmpty())
            <section class="et-article-aside__card">
                <div class="et-article-aside__head">
                    <h2 class="et-article-aside__heading">Article details</h2>
                </div>
                <div class="et-article-aside__meta">
                    @foreach($articleMeta as $meta)
                        @if(! empty($meta['url']))
                            <a class="et-article-aside__meta-item et-article-aside__meta-item--{{ $meta['tone'] ?? 'soft' }}" href="{{ $meta['url'] }}">
                                {{ $meta['label'] }}
                            </a>
                        @else
                            <span class="et-article-aside__meta-item et-article-aside__meta-item--{{ $meta['tone'] ?? 'soft' }}">
                                @if(! empty($meta['datetime']))
                                    <time datetime="{{ $meta['datetime'] }}">{{ $meta['label'] }}</time>
                                @else
                                    {{ $meta['label'] }}
                                @endif
                            </span>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if($author)
            <section class="et-article-aside__card et-article-aside__author">
                <div class="et-article-aside__author-top">
                    <div class="et-article-aside__avatar" style="--ua-bg: {{ $author['color'] }}">
                        @if(! empty($author['avatar_url']))
                            <img src="{{ $author['avatar_url'] }}" alt="" loading="lazy" width="56" height="56">
                        @else
                            <span aria-hidden="true">{{ $author['initials'] }}</span>
                        @endif
                    </div>
                    <div class="et-article-aside__author-copy">
                        <p class="et-article-aside__label">Written by</p>
                        <h2 class="et-article-aside__author-name">
                            @if(! empty($author['profile_url']))
                                <a href="{{ $author['profile_url'] }}">{{ $author['name'] }}</a>
                            @else
                                {{ $author['name'] }}
                            @endif
                        </h2>
                    </div>
                </div>
                @if(! empty($author['bio']))
                    <p class="et-article-aside__author-bio">{{ $author['bio'] }}</p>
                @endif
                @if(! empty($author['profile_url']))
                    <a class="et-article-aside__cta" href="{{ $author['profile_url'] }}">
                        View profile
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif
            </section>
        @endif

        @if($tags->isNotEmpty())
            <section class="et-article-aside__card">
                <div class="et-article-aside__head">
                    <h2 class="et-article-aside__heading">Tags</h2>
                </div>
                <div class="et-article-aside__tags">
                    @foreach($tags as $tag)
                        <a class="et-article-aside__tag" href="{{ $tag['url'] }}">{{ $tag['name'] }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($categories->isNotEmpty())
            <section class="et-article-aside__card">
                <div class="et-article-aside__head">
                    <h2 class="et-article-aside__heading">Categories</h2>
                </div>
                <ul class="et-article-aside__cats">
                    @foreach($categories as $category)
                        <li>
                            <a
                                class="et-article-aside__cat{{ ! empty($category['is_current']) ? ' is-current' : '' }}"
                                href="{{ $category['url'] }}"
                                @if(! empty($category['is_current'])) aria-current="page" @endif
                            >
                                <span class="et-article-aside__cat-main">
                                    <span class="et-article-aside__cat-name">{{ $category['name'] }}</span>
                                    @if(! empty($category['is_current']) && ! empty($category['description']))
                                        <span class="et-article-aside__cat-desc">{{ $category['description'] }}</span>
                                    @endif
                                </span>
                                <span class="et-article-aside__cat-arrow" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($latest->isNotEmpty())
            <section class="et-article-aside__card">
                <div class="et-article-aside__head">
                    <h2 class="et-article-aside__heading">{{ $latestTitle }}</h2>
                    <a class="et-article-aside__all" href="{{ $viewAllUrl }}">{{ $viewAllLabel }}</a>
                </div>
                <ul class="et-article-aside__latest">
                    @foreach($latest as $item)
                        @php $hasThumb = ! empty($item['image']); @endphp
                        <li>
                            <a
                                class="et-article-aside__latest-item{{ $hasThumb ? '' : ' et-article-aside__latest-item--text' }}"
                                href="{{ $item['url'] }}"
                            >
                                @if($hasThumb)
                                    <span class="et-article-aside__thumb" aria-hidden="true">
                                        <img src="{{ $item['image'] }}" alt="" loading="lazy" width="72" height="54">
                                    </span>
                                @else
                                    <span class="et-article-aside__index" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                @endif
                                <span class="et-article-aside__latest-body">
                                    @if(! empty($item['kicker']))
                                        <span class="et-article-aside__latest-kicker">{{ $item['kicker'] }}</span>
                                    @endif
                                    <span class="et-article-aside__latest-title">{{ $item['title'] }}</span>
                                    @if(! empty($item['meta']))
                                        <span class="et-article-aside__latest-meta">{{ $item['meta'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </aside>
@endif
