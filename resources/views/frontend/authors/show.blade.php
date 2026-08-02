@extends('frontend.layouts.app')

@php
    $avatar = user_avatar($author);
    $bio = trim((string) ($author->profile?->bio ?? ''));
    $shareRawUrl = url()->current();
    $shareUrl = urlencode($shareRawUrl);
    $shareText = urlencode($author->name.' on Examtube');
    $crumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Authors', 'url' => route('frontend.authors.index')],
        ['label' => 'Profile'],
    ];
    $seo = [
        'title' => $author->name.' · Author',
        'description' => \Illuminate\Support\Str::limit(
            $bio !== '' ? $bio : ($author->name.' shares exams, blogs, news, and practice questions on Examtube.'),
            160
        ),
        'type' => 'profile',
        'image' => seo_image($avatar['url'] ?? null, 'profile'),
        'image_type' => 'profile',
        'breadcrumbs' => $crumbs,
    ];
    $socialLabels = [
        'website' => 'Website',
        'linkedin' => 'LinkedIn',
        'x' => 'X',
        'twitter' => 'X',
        'github' => 'GitHub',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
    ];
@endphp

@section('content')
    <x-ad-layout page="author_detail">
    <article class="et-author-page">
        <header class="et-author-hero">
            <div class="et-container et-author-page__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $crumbs])

                <div class="et-author-hero__panel">
                    <div class="et-author-hero__avatar" style="--ua-bg: {{ $avatar['color'] }}">
                        @if($avatar['url'])
                            <img src="{{ $avatar['url'] }}" alt="{{ $author->name }}" width="112" height="112">
                        @else
                            <span aria-hidden="true">{{ $avatar['initials'] }}</span>
                        @endif
                    </div>

                    <div class="et-author-hero__copy">
                        <p class="et-author-hero__eyebrow">Author profile</p>
                        <h1>{{ $author->name }}</h1>

                        @if($bio !== '')
                            <p class="et-author-hero__bio">{{ $bio }}</p>
                        @endif

                        @if(! empty($authorSocialLinks))
                            <ul class="et-author-hero__social" aria-label="Author social links">
                                @foreach($authorSocialLinks as $network => $url)
                                    @php
                                        $platform = strtolower((string) $network);
                                        $label = $socialLabels[$platform] ?? ucfirst(str_replace('_', ' ', $platform));
                                    @endphp
                                    <li>
                                        <a href="{{ $url }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="et-author-hero__social-link"
                                           title="{{ $label }}">
                                            <span class="et-visually-hidden">{{ $label }}</span>
                                            @include('backend.partials.social-platform-icon', ['platform' => $platform, 'size' => 16])
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="et-author-hero__share">
                        @include('frontend.partials.article-share', [
                            'shareUrl' => $shareUrl,
                            'shareText' => $shareText,
                            'shareRawUrl' => $shareRawUrl,
                            'shareLabel' => 'Share profile',
                        ])
                    </div>
                </div>
            </div>
        </header>

        <x-ad-slot page="author_detail" position="below_title" />

        <div class="et-container et-author-page__wrap et-author-work">
            <section class="et-author-work__block">
                <div class="et-author-work__head">
                    <div>
                        <h2>Latest exams</h2>
                        <p>Practice tests published by this author</p>
                    </div>
                    @if($exams->isNotEmpty() && Route::has('frontend.exams.index'))
                        <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--ghost et-btn--sm">All exams</a>
                    @endif
                </div>
                @if($exams->isEmpty())
                    <p class="et-author-work__empty">No public exams yet.</p>
                @else
                    <div class="et-grid et-grid--3">
                        @foreach($exams as $exam)
                            @include('frontend.components.exam-card', ['exam' => $exam])
                        @endforeach
                    </div>
                @endif
            </section>

            <x-ad-slot page="author_detail" position="between_sections" />

            <section class="et-author-work__block">
                <div class="et-author-work__head">
                    <div>
                        <h2>Latest blogs</h2>
                        <p>Guides and insights from this author</p>
                    </div>
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

            <x-ad-slot page="author_detail" position="after_blogs" />

            <section class="et-author-work__block">
                <div class="et-author-work__head">
                    <div>
                        <h2>Latest news</h2>
                        <p>Updates and announcements from this author</p>
                    </div>
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

            <x-ad-slot page="author_detail" position="after_news" />

            <section class="et-author-work__block">
                <div class="et-author-work__head">
                    <div>
                        <h2>Latest questions</h2>
                        <p>Practice questions contributed by this author</p>
                    </div>
                    @if($questions->isNotEmpty() && Route::has('frontend.questions.index'))
                        <a href="{{ route('frontend.questions.index') }}" class="et-btn et-btn--ghost et-btn--sm">All questions</a>
                    @endif
                </div>
                @if($questions->isEmpty())
                    <p class="et-author-work__empty">No public questions yet.</p>
                @else
                    <div class="et-grid et-grid--3">
                        @foreach($questions as $question)
                            @include('frontend.components.question-card', ['question' => $question])
                        @endforeach
                    </div>
                @endif
            </section>

            <x-ad-slot page="author_detail" position="after_content" />
        </div>
    </article>
    </x-ad-layout>
@endsection
