@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => site_setting('brand.tagline', site_setting('brand.site_name', 'Practice smarter. Score higher.')),
        'description' => $siteSettings['seo.default_description']
            ?? ($siteBrand['description'] ?? ($siteSettings['brand.tagline'] ?? 'Practice smarter with exams, questions, blogs, and news.')),
        'type' => 'website',
        'image_type' => 'home',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
        ],
    ];
@endphp

@section('content')
    @php
        $sections = $page['sections'] ?? collect();
        $order = [
            'hero' => 'after_hero',
            'stats' => 'after_stats',
            'featured_exams' => 'after_featured_exams',
            'questions' => 'after_questions',
            'categories' => 'after_categories',
            'blogs' => 'after_blogs',
            'news' => 'after_news',
            'testimonials' => 'after_testimonials',
            'faqs' => 'after_faqs',
            'newsletter' => 'after_newsletter',
            'cta' => 'after_cta',
        ];
    @endphp

    <x-ad-layout page="home">
        @foreach($order as $key => $afterPosition)
            @includeIf('frontend.home.partials.'.$key, [
                'section' => $sections->get($key),
                'page' => $page,
            ])
            <x-ad-slot page="home" :position="$afterPosition" />
        @endforeach
    </x-ad-layout>
@endsection
