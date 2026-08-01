@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Home',
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
            'hero',
            'stats',
            'featured_exams',
            'questions',
            'categories',
            'blogs',
            'news',
            'testimonials',
            'faqs',
            'partners',
            'newsletter',
            'cta',
        ];
    @endphp

    @foreach($order as $key)
        @includeIf('frontend.home.partials.'.$key, [
            'section' => $sections->get($key),
            'page' => $page,
        ])
    @endforeach
@endsection
