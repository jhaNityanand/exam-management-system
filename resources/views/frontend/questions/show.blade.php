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
    $shareRawUrl = url()->current();
    $inputType = ! empty($payload['multiple']) ? 'checkbox' : 'radio';
    $publicTags = collect($question->public_tags ?? [])
        ->map(fn ($tag) => is_array($tag) ? ($tag['name'] ?? '') : $tag)
        ->map(fn ($tag) => ltrim(trim((string) $tag), '#'))
        ->filter()
        ->unique(fn ($tag) => mb_strtolower($tag))
        ->values();
@endphp

@section('content')
<article class="et-qd">
        <div class="et-qd__top">
            <div class="et-container et-qd__wrap">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
                <h1>Practice question</h1>
                <p>Review the question, correct answer, and explanation.</p>
                @include('frontend.partials.ad-placement', ['page' => 'question_detail', 'position' => 'below_title'])
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
            @include('frontend.partials.ad-placement', ['page' => 'question_detail', 'position' => 'before_content'])

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
                        @include('frontend.partials.ad-placement', ['page' => 'question_detail', 'position' => 'after_options'])
                    @endif

                    @if(! empty($payload['explanation']))
                        <section class="et-qd__panel et-qd__explain">
                            <div class="et-qd__section-head">
                                <h4>Explanation</h4>
                            </div>
                            <div class="et-prose et-qd__explain-body">{!! $payload['explanation'] !!}</div>
                        </section>
                        @include('frontend.partials.ad-placement', ['page' => 'question_detail', 'position' => 'after_explanation'])
                    @endif

                    <div class="et-article__footer-panel et-qd__share-panel">
                        @include('frontend.partials.article-share', [
                            'shareUrl' => $shareUrl,
                            'shareText' => $shareText,
                            'shareRawUrl' => $shareRawUrl,
                            'shareLabel' => 'Share this question',
                        ])
                    </div>
                    @include('frontend.partials.ad-placement', ['page' => 'question_detail', 'position' => 'after_share'])

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
                        @include('frontend.partials.ad-placement', ['page' => 'question_detail', 'position' => 'after_related'])
                    @endif

            </main>
            @include('frontend.questions.partials.aside')
            </div>
</div>
    </article>
@endsection
