@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Frequently Asked Questions',
        'description' => 'Find answers to common questions about exams, accounts, and using '.($siteBrand['name'] ?? config('app.name')).'.',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'FAQs'],
        ],
    ];
@endphp

@section('content')
<section class="et-page-hero">
    <div class="et-container et-page-hero__inner">
        @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $seo['breadcrumbs']])
        <p class="et-eyebrow">Help Center</p>
        <h1>Frequently Asked Questions</h1>
        <p class="et-page-hero__lead">Quick answers to help you prepare, attempt exams, and manage your account.</p>
    </div>
</section>

<section class="et-section">
    <div class="et-container" style="max-width:820px;margin-inline:auto">
        @if($faqs->isEmpty())
            @include('frontend.partials.empty-state', [
                'title' => 'No FAQs yet',
                'message' => 'Check back soon — we are preparing helpful answers.',
            ])
        @else
            @include('frontend.components.faq-accordion', ['faqs' => $faqs])
        @endif
    </div>
</section>
@endsection
