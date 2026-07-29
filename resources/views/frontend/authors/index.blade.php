@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Authors',
        'description' => 'Meet the mentors and editors who publish exams, blogs, and news on '.($siteBrand['name'] ?? 'Examtube').'.',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Authors'],
        ],
    ];
@endphp

@section('content')
<section class="et-page-hero">
    <div class="et-container et-page-hero__inner">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $seo['breadcrumbs']])
        <p class="et-eyebrow">Community</p>
        <h1>Meet our authors</h1>
        <p class="et-page-hero__lead">Mentors and editors who publish exams, blogs, and news.</p>
    </div>
</section>

<section class="et-section">
    <div class="et-container">
        @if($authors->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No public authors yet',
                'message' => 'Contributors will appear here once published.',
            ])
        @else
            <div class="et-grid et-grid--3">
                @foreach($authors as $author)
                    @php $avatar = user_avatar($author); @endphp
                    <a href="{{ route('frontend.authors.show', $author->slug) }}" class="et-card et-author-card">
                        <div class="et-card__body" style="display:flex;align-items:center;gap:.9rem">
                            <span class="et-profile__avatar" style="--ua-bg: {{ $avatar['color'] }};width:3rem;height:3rem;font-size:.9rem">
                                @if($avatar['url'])
                                    <img src="{{ $avatar['url'] }}" alt="" loading="lazy">
                                @else
                                    {{ $avatar['initials'] }}
                                @endif
                            </span>
                            <span>
                                <strong style="display:block">{{ $author->name }}</strong>
                                <span class="et-card__meta">{{ \Illuminate\Support\Str::limit($author->profile?->bio ?? 'Examtube contributor', 70) }}</span>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
