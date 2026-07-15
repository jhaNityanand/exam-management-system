@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => $exam->meta_title ?: $exam->title,
        'description' => $exam->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $exam->description), 160),
        'keywords' => $exam->meta_keywords,
        'canonical' => $exam->canonical_url ?: url()->current(),
    ];
    $isFree = ($exam->pricing_option ?? 'free') === 'free' || (float) ($exam->exam_amount ?? 0) <= 0;
@endphp

@section('content')
    <div class="et-page-hero">
        <div class="et-container">
            @include('frontend.partials.breadcrumbs', ['breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Exams', 'url' => route('frontend.exams.index')],
                ['label' => $exam->title],
            ]])
            <div class="et-card__meta" style="margin-bottom:.5rem">
                @if($exam->category)
                    <span class="et-badge">{{ $exam->category->name }}</span>
                @endif
                @if($exam->difficulty_level)
                    <span class="et-badge et-badge--slate">{{ ucfirst($exam->difficulty_level) }}</span>
                @endif
                <span class="et-badge">{{ $isFree ? 'Free' : 'Paid' }}</span>
            </div>
            <h1>{{ $exam->title }}</h1>
            @if($exam->description)
                <p>{{ \Illuminate\Support\Str::limit(strip_tags($exam->description), 200) }}</p>
            @endif
        </div>
    </div>

    <div class="et-container" style="padding:1.5rem 0 3rem;display:grid;gap:1.25rem;grid-template-columns:1fr">
        <div class="et-grid et-grid--4">
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->duration ?? 0) }}</span><span class="et-stat__label">Minutes</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->total_questions ?? 0) }}</span><span class="et-stat__label">Questions</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->total_marks ?? 0) }}</span><span class="et-stat__label">Total marks</span></div>
            <div class="et-stat"><span class="et-stat__value">{{ (int) ($exam->pass_percentage ?? $exam->passing_marks ?? 0) }}</span><span class="et-stat__label">Pass mark</span></div>
        </div>

        <div class="et-card" style="padding:1.25rem">
            <h2 style="margin-top:0">About this exam</h2>
            <div class="et-prose">
                {!! $exam->description ? nl2br(e($exam->description)) : '<p>No description provided.</p>' !!}
            </div>
            @if($exam->instructions)
                <h3>Instructions</h3>
                <div class="et-prose">{!! nl2br(e($exam->instructions)) !!}</div>
            @endif
            <div style="margin-top:1.25rem;display:flex;gap:.65rem;flex-wrap:wrap">
                @auth
                    <a href="{{ route('frontend.account.exams') }}" class="et-btn et-btn--primary">Go to my exams</a>
                @else
                    <a href="{{ route('login') }}" class="et-btn et-btn--primary">Login to attempt</a>
                    <a href="{{ route('register') }}" class="et-btn et-btn--ghost">Create account</a>
                @endauth
            </div>
        </div>
    </div>
@endsection
