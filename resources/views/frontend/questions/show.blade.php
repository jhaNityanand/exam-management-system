@extends('frontend.layouts.app')

@php
    $title = $question->publicTitle();
    $seoDescriptionSource = $question->meta_description
        ?: (($question->show_explanation_publicly && filled($question->explanation))
            ? $question->explanation
            : $question->body);
    $seo = [
        'title' => $question->meta_title ?: $title,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $seoDescriptionSource), 160),
        'keywords' => $question->meta_keywords,
        'canonical' => $question->canonical_url ?: url()->current(),
        'og_title' => $question->og_title ?: $title,
        'og_description' => $question->og_description,
        'type' => 'article',
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($title);
@endphp

@section('content')
    <article class="et-article">
        <div class="et-page-hero">
            <div class="et-container et-article__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Questions', 'url' => route('frontend.questions.index')],
                    ['label' => $title],
                ]])
                <div class="et-card__meta">
                    @if($question->category)
                        <a class="et-badge" href="{{ route('frontend.questions.category', $question->category->slug) }}">{{ $question->category->name }}</a>
                    @endif
                    <span class="et-badge et-badge--soft">{{ ucfirst((string) $question->difficulty) }}</span>
                    <span class="et-badge et-badge--soft">{{ strtoupper(str_replace('_', ' ', (string) $question->type)) }}</span>
                </div>
                <h1>{{ $title }}</h1>
            </div>
        </div>

        <div class="et-container et-article__wrap et-section">
            <div class="et-prose">
                {!! $question->body !!}
            </div>

            @if(!empty($payload['options']))
                <section class="et-question-options">
                    <h2>Options</h2>
                    <ul>
                        @foreach($payload['options'] as $option)
                            <li><span>{{ $option['text'] }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($payload['explanation']))
                <section class="et-callout et-callout--success">
                    <h2>Explanation</h2>
                    <div class="et-prose">{!! $payload['explanation'] !!}</div>
                </section>
            @endif

            @if(!empty($question->public_tags))
                <div class="et-tag-cloud">
                    @foreach($question->public_tags as $tag)
                        <span>#{{ is_array($tag) ? ($tag['name'] ?? '') : $tag }}</span>
                    @endforeach
                </div>
            @endif

            <div class="et-share">
                <span>Share</span>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}" target="_blank" rel="noopener">X</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener">Facebook</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener">LinkedIn</a>
                <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener">WhatsApp</a>
            </div>

            <nav class="et-pager" aria-label="Question navigation">
                @if($previous)
                    <a class="et-pager__link" href="{{ route('frontend.questions.show', $previous) }}">
                        <span>Previous</span>
                        <strong>{{ $previous->publicTitle() }}</strong>
                    </a>
                @else
                    <span></span>
                @endif
                @if($next)
                    <a class="et-pager__link et-pager__link--next" href="{{ route('frontend.questions.show', $next) }}">
                        <span>Next</span>
                        <strong>{{ $next->publicTitle() }}</strong>
                    </a>
                @endif
            </nav>

            @if(($related ?? collect())->isNotEmpty())
                <section class="et-related">
                    @include('frontend.components.section-heading', ['title' => 'Related questions', 'subtitle' => ''])
                    <div class="et-grid et-grid--2">
                        @foreach($related as $item)
                            @include('frontend.components.question-card', ['question' => $item])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </article>
@endsection
