@extends('frontend.layouts.app')

@php
    $title = $question->publicTitle();
    $seoDescriptionSource = $question->meta_description
        ?: (($question->show_explanation_publicly && filled($question->explanation))
            ? $question->explanation
            : $question->body);
    $breadcrumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Questions', 'url' => route('frontend.questions.index')],
    ];
    if ($question->category) {
        $breadcrumbs[] = [
            'label' => $question->category->name,
            'url' => route('frontend.questions.category', $question->category->slug),
        ];
    }
    $breadcrumbs[] = ['label' => 'Practice'];
    $seo = [
        'title' => $question->meta_title ?: $title,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $seoDescriptionSource), 160),
        'keywords' => $question->meta_keywords,
        'canonical' => $question->canonical_url ?: url()->current(),
        'og_title' => $question->og_title ?: $title,
        'og_description' => $question->og_description,
        'image' => $question->seoImageUrl(),
        'image_type' => 'question',
        'type' => 'article',
        'breadcrumbs' => $breadcrumbs,
    ];
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($title);
    $difficulty = strtolower((string) $question->difficulty);
    $difficultyBadge = match ($difficulty) {
        'easy' => 'et-badge--success',
        'hard' => 'et-badge--danger',
        default => 'et-badge--warn',
    };
    $inputType = ! empty($payload['multiple']) ? 'checkbox' : 'radio';
@endphp

@section('content')
<article class="et-qd">
        <div class="et-qd__top">
            <div class="et-container et-qd__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
                <h1>Practice question</h1>
                <p>Review the question, correct answer, and explanation.</p>
            </div>
        </div>

<div class="et-container et-qd__wrap et-qd__main">
            <div class="et-qd__layout">
            <main class="et-qd__content">
            <section class="et-qd__panel et-qd__question" aria-labelledby="et-qd-question-heading">
                <p class="et-qd__kicker">Question</p>
                <h3 id="et-qd-question-heading" class="et-qd__prompt">
                    @if(str_contains((string) $question->body, '<'))
                        {!! $question->body !!}
                    @else
                        {!! nl2br(e((string) ($question->body ?: $title))) !!}
                    @endif
                </h3>
            </section>

@if(! empty($payload['show_options']))
                        <section class="et-qd__panel et-qd__options" aria-label="Answer options">
                            <div class="et-qd__section-head">
                                <h4>Options</h4>
                                <span>{{ ! empty($payload['multiple']) ? 'Select all that apply' : 'Choose one' }} · Correct answer highlighted</span>
                            </div>
                            <div class="et-qd__option-list" role="list">
                                @foreach($payload['options'] as $option)
                                    <label class="et-qd__option {{ ! empty($option['is_correct']) ? 'is-correct' : '' }}" role="listitem">
                                        <input
                                            type="{{ $inputType }}"
                                            name="et-qd-answer"
                                            value="{{ $option['key'] }}"
                                            @checked(! empty($option['is_correct']))
                                            disabled
                                            tabindex="-1"
                                        >
                                        <span class="et-qd__option-letter" aria-hidden="true">{{ $option['letter'] }}</span>
                                        <span class="et-qd__option-text">{{ $option['text'] }}</span>
                                        @if(! empty($option['is_correct']))
                                            <span class="et-qd__option-pill">Correct</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if(! empty($payload['explanation']))
                        <section class="et-qd__panel et-qd__explain">
                            <div class="et-qd__section-head">
                                <h4>Explanation</h4>
                            </div>
                            <div class="et-prose et-qd__explain-body">{!! $payload['explanation'] !!}</div>
                        </section>
                    @endif

                    <div class="et-qd__meta-row">
                        @if(! empty($question->public_tags))
                            <div class="et-qd__tags" aria-label="Tags">
                                <span class="et-qd__tags-label">Tags</span>
                                <div class="et-qd__tags-list">
                                    @foreach($question->public_tags as $tag)
                                        @php
                                            $tagName = is_array($tag) ? ($tag['name'] ?? '') : $tag;
                                            $tagName = ltrim((string) $tagName, '#');
                                        @endphp
                                        @continue($tagName === '')
                                        <span class="et-qd__tag">#{{ $tagName }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="et-qd__share" aria-label="Share this question">
                            <span class="et-qd__share-label">Share</span>
                            <div class="et-qd__share-actions">
                                <a class="et-qd__share-btn et-qd__share-btn--whatsapp"
                                   href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Share on WhatsApp">
                                    <span class="et-visually-hidden">Share on WhatsApp</span>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M17.47 14.38c-.29-.15-1.73-.85-2-.95-.27-.1-.46-.15-.66.15-.2.29-.76.95-.93 1.14-.17.2-.34.22-.63.07-.29-.15-1.23-.45-2.34-1.45-.86-.77-1.45-1.72-1.62-2.01-.17-.29-.02-.45.13-.6.13-.13.29-.34.43-.51.15-.17.2-.29.29-.49.1-.2.05-.37-.02-.52-.07-.15-.66-1.59-.9-2.18-.24-.57-.48-.49-.66-.5h-.57c-.2 0-.52.07-.79.37-.27.29-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49 1.9.82 2.4.74 2.84.69.43-.05 1.4-.57 1.6-1.12.2-.55.2-1.02.14-1.12-.06-.1-.26-.16-.55-.31zM12.05 2.01a9.9 9.9 0 00-8.54 14.85L2 22l5.28-1.38a9.9 9.9 0 004.77 1.21h.01a9.9 9.9 0 000-19.8zm0 18.08h-.01a8.2 8.2 0 01-4.17-1.14l-.3-.18-3.13.82.84-3.05-.2-.31a8.22 8.22 0 1111.17 3.86z"/>
                                    </svg>
                                </a>
                                <a class="et-qd__share-btn et-qd__share-btn--linkedin"
                                   href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Share on LinkedIn">
                                    <span class="et-visually-hidden">Share on LinkedIn</span>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
                                        <g transform="translate(1.5 0) scale(0.046875)">
                                            <path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 01107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/>
                                        </g>
                                    </svg>
                                </a>
                                <a class="et-qd__share-btn et-qd__share-btn--instagram"
                                   href="https://www.instagram.com/"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Copy link and open Instagram"
                                   data-share-copy="{{ url()->current() }}">
                                    <span class="et-visually-hidden">Share on Instagram</span>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.64-.07-4.85s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zm0-2.16C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 100 12.32 6.16 6.16 0 000-12.32zM12 16a4 4 0 110-8 4 4 0 010 8zm6.41-11.85a1.44 1.44 0 100 2.88 1.44 1.44 0 000-2.88z"/>
                                    </svg>
                                </a>
                                <a class="et-qd__share-btn et-qd__share-btn--x"
                                   href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="Share on X">
                                    <span class="et-visually-hidden">Share on X</span>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.732-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

@if(($relatedBlogs ?? collect())->isNotEmpty())
                        <section class="et-qd__related">
                            @include('frontend.components.section-heading', [
                                'title' => 'Related blogs',
                                'subtitle' => 'Guides and insights connected to this topic',
                            ])
                    <div class="et-grid et-grid--3">
                        @foreach($relatedBlogs as $blog)
                            @include('frontend.components.blog-card', ['blog' => $blog])
                        @endforeach
                    </div>
                </section>
            @endif

            </main>
            <aside class="et-qd__aside" aria-label="Question details">
                <section class="et-qd__aside-card">
                    <p class="et-qd__aside-eyebrow">Question details</p>
                    <h2>Practice overview</h2>
                    <div class="et-qd__aside-meta">
                        @if($question->category)
                            <a class="et-badge et-qd__badge" href="{{ route('frontend.questions.category', $question->category->slug) }}">
                                {{ $question->category->name }}
                            </a>
                        @endif
                        <span class="et-badge {{ $difficultyBadge }} et-qd__badge">{{ $question->difficultyLabel() }}</span>
                        <span class="et-badge et-badge--info et-qd__badge">{{ $question->typeLabel() }}</span>
                    </div>
                    <p class="et-qd__aside-note">Review the answer and explanation, then explore related learning resources below.</p>
                </section>
            </aside>
            </div>
</div>
    </article>
@endsection
