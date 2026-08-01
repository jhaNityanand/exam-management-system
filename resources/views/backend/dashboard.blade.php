@extends('backend.layouts.app')

@section('title', 'Admin Dashboard — ExamMS')
@section('page-title', 'Dashboard')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dashboard'],
    ]" />
@endsection

@section('content')
@php
    $statCards = [
        [
            'label' => 'Total Questions',
            'value' => number_format($stats['total_questions']),
            'href' => route('admin.questions.index'),
            'tone' => 'blue',
            'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label' => 'Question Categories',
            'value' => number_format($stats['total_question_categories']),
            'href' => route('admin.questions.categories.index'),
            'tone' => 'indigo',
            'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
        ],
        [
            'label' => 'Total Exams',
            'value' => number_format($stats['total_exams']),
            'href' => route('admin.exams.index'),
            'tone' => 'rose',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        ],
        [
            'label' => 'Exam Categories',
            'value' => number_format($stats['total_exam_categories']),
            'href' => route('admin.exams.categories.index'),
            'tone' => 'orange',
            'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        ],
        [
            'label' => 'Total Blogs',
            'value' => number_format($stats['total_blogs']),
            'href' => route('admin.blogs.index'),
            'tone' => 'violet',
            'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
        ],
        [
            'label' => 'Blog Categories',
            'value' => number_format($stats['total_blog_categories']),
            'href' => route('admin.blogs.categories.index'),
            'tone' => 'fuchsia',
            'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        ],
        [
            'label' => 'Total News',
            'value' => number_format($stats['total_news']),
            'href' => route('admin.news.index'),
            'tone' => 'cyan',
            'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
        ],
        [
            'label' => 'News Categories',
            'value' => number_format($stats['total_news_categories']),
            'href' => route('admin.news.categories.index'),
            'tone' => 'sky',
            'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
        ],
        [
            'label' => 'Gallery Images',
            'value' => number_format($stats['total_gallery_images']),
            'href' => route('admin.gallery.index'),
            'tone' => 'teal',
            'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        [
            'label' => 'Gallery Categories',
            'value' => number_format($stats['total_gallery_categories']),
            'href' => route('admin.gallery.index'),
            'tone' => 'emerald',
            'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
        ],
        [
            'label' => 'Total Candidates',
            'value' => number_format($stats['total_candidates']),
            'href' => route('admin.candidates.index'),
            'tone' => 'emerald',
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        ],
        [
            'label' => 'Organization Members',
            'value' => number_format($stats['total_organization_members']),
            'href' => route('admin.settings.organization.members.index'),
            'tone' => 'teal',
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        ],
        [
            'label' => 'Total Organizations',
            'value' => number_format($stats['total_organizations']),
            'href' => route('admin.settings.organization'),
            'tone' => 'slate',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        ],
        [
            'label' => 'Total Notifications',
            'value' => number_format($stats['total_notifications']),
            'href' => route('admin.notifications.index'),
            'tone' => 'amber',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        ],
        [
            'label' => 'Total Transactions',
            'value' => number_format($stats['total_transactions']),
            'href' => route('admin.transactions.index'),
            'tone' => 'violet',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        ],
    ];

    $chartPanels = [
        ['id' => 'questionsByCategoryChart', 'title' => 'Questions by Category', 'empty' => empty($stats['charts']['questions_by_category']['labels'])],
        ['id' => 'examsByStatusChart', 'title' => 'Exams by Status', 'empty' => false],
        ['id' => 'examAttemptsChart', 'title' => 'Exam Attempts (Last 7 Days)', 'empty' => false],
        ['id' => 'candidateRegistrationsChart', 'title' => 'Candidate Registrations', 'empty' => false],
        ['id' => 'blogVsNewsChart', 'title' => 'Blog vs News Count', 'empty' => false],
        ['id' => 'membersByRoleChart', 'title' => 'Organization Members by Role', 'empty' => false],
        ['id' => 'monthlyContentGrowthChart', 'title' => 'Monthly Content Growth', 'empty' => false],
        ['id' => 'contentDistributionChart', 'title' => 'Overall Content Distribution', 'empty' => false],
    ];
@endphp

<div class="dashboard-page">
    <div class="dashboard-stats-grid mb-6 sm:mb-8">
        @foreach ($statCards as $card)
            <x-backend.stat-card
                :label="$card['label']"
                :value="$card['value']"
                :href="$card['href']"
                :tone="$card['tone']"
                :icon="$card['icon']"
            />
        @endforeach
    </div>

    <div class="dashboard-charts-grid">
        @foreach ($chartPanels as $panel)
            <section class="dashboard-chart-card">
                <h3 class="dashboard-chart-card__title">{{ $panel['title'] }}</h3>
                <div class="dashboard-chart-card__canvas">
                    <canvas id="{{ $panel['id'] }}" aria-label="{{ $panel['title'] }}"></canvas>
                </div>
                @if ($panel['empty'])
                    <p class="dashboard-chart-card__empty">No data yet.</p>
                @endif
            </section>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/dashboard.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    window.dashboardChartsConfig = @json($stats['charts']);
</script>
<script src="{{ versioned_asset('js/backend/dashboard-charts.js') }}"></script>
@endpush
