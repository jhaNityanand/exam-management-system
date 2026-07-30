@extends('frontend.layouts.app')

@php
    $avatar = user_avatar($author);
    $role = $role ?? author_role($author);
    $seo = [
        'title' => $author->name.' · Author',
        'description' => \Illuminate\Support\Str::limit($author->profile?->bio ?: ($author->name.' contributes exams, blogs, and news on Examtube.'), 160),
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Authors', 'url' => route('frontend.authors.index')],
            ['label' => $author->name],
        ],
    ];
@endphp

@section('content')
    <section class="et-author-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $seo['breadcrumbs']])
            <div class="et-author-hero__panel">
                <div class="et-author-hero__avatar" style="--ua-bg: {{ $avatar['color'] }}">
                    @if($avatar['url'])
                        <img src="{{ $avatar['url'] }}" alt="" width="112" height="112">
                    @else
                        <span aria-hidden="true">{{ $avatar['initials'] }}</span>
                    @endif
                </div>
                <div class="et-author-hero__copy">
                    <div class="et-author-hero__meta">
                        <span class="et-author-card__role et-author-card__role--{{ $role['key'] }}">{{ $role['label'] }}</span>
                        @if(($stats['blogs'] ?? 0) > 0 || ($stats['news'] ?? 0) > 0)
                            <span class="et-author-hero__counts">
                                @if(($stats['blogs'] ?? 0) > 0)
                                    <strong>{{ $stats['blogs'] }}</strong> blogs
                                @endif
                                @if(($stats['blogs'] ?? 0) > 0 && ($stats['news'] ?? 0) > 0)
                                    <span aria-hidden="true">·</span>
                                @endif
                                @if(($stats['news'] ?? 0) > 0)
                                    <strong>{{ $stats['news'] }}</strong> news
                                @endif
                            </span>
                        @endif
                    </div>
                    <h1>{{ $author->name }}</h1>
                    <p class="et-author-hero__bio">{{ $author->profile?->bio ?: 'Contributor on Examtube.in — sharing practice resources, guides, and exam updates.' }}</p>

                    @if(! empty($socialLinks))
                        <ul class="et-author-hero__social">
                            @foreach($socialLinks as $network => $url)
                                <li>
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ucfirst(str_replace('_', ' ', $network)) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="et-container et-section et-author-work">
        <section class="et-author-work__block">
            <div class="et-author-work__head">
                <h2>Latest blogs</h2>
                @if($blogs->isNotEmpty())
                    <a href="{{ route('frontend.blogs.index') }}" class="et-btn et-btn--ghost et-btn--sm">All blogs</a>
                @endif
            </div>
            @if($blogs->isEmpty())
                <p class="et-author-work__empty">No published blogs yet.</p>
            @else
                <div class="et-grid et-grid--3">
                    @foreach($blogs as $blog)
                        @include('frontend.components.blog-card', ['blog' => $blog])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="et-author-work__block">
            <div class="et-author-work__head">
                <h2>Latest news</h2>
                @if($news->isNotEmpty())
                    <a href="{{ route('frontend.news.index') }}" class="et-btn et-btn--ghost et-btn--sm">All news</a>
                @endif
            </div>
            @if($news->isEmpty())
                <p class="et-author-work__empty">No published news yet.</p>
            @else
                <div class="et-grid et-grid--3">
                    @foreach($news as $item)
                        @include('frontend.components.news-card', ['news' => $item])
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
