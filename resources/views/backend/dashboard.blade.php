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

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
    @php
        $cards = [
            [
                'label' => 'Total Questions',
                'value' => number_format($stats['total_questions']),
                'href' => route('admin.questions.index'),
                'icon_wrap' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
                'blob' => 'from-blue-100 to-blue-50 dark:from-blue-900/40 dark:to-blue-900/10',
                'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Question Categories',
                'value' => number_format($stats['total_categories']),
                'href' => route('admin.questions.categories.index'),
                'icon_wrap' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
                'blob' => 'from-indigo-100 to-indigo-50 dark:from-indigo-900/40 dark:to-indigo-900/10',
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            ],
            [
                'label' => 'Workspace Members',
                'value' => number_format($stats['total_members']),
                'href' => route('admin.candidates.index'),
                'icon_wrap' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                'blob' => 'from-emerald-100 to-emerald-50 dark:from-emerald-900/40 dark:to-emerald-900/10',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            ],
            [
                'label' => 'Active Exams',
                'value' => number_format($stats['active_exams']),
                'href' => route('admin.exams.index'),
                'icon_wrap' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
                'blob' => 'from-rose-100 to-rose-50 dark:from-rose-900/40 dark:to-rose-900/10',
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
            ],
        ];
    @endphp

    @foreach ($cards as $card)
    <a href="{{ $card['href'] }}" class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-5 sm:p-6 shadow-sm transition hover:shadow-md group block">
        <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $card['blob'] }} opacity-50 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ $card['value'] }}</p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $card['icon_wrap'] }} shadow-inner">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                </svg>
            </div>
        </div>
    </a>
    @endforeach
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm p-4 sm:p-6">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Questions by Category</h3>
        <div class="relative h-56 sm:h-64 w-full">
            <canvas id="categoryQuestionsChart"></canvas>
        </div>
        @if (empty($stats['category_chart']['labels']))
            <p class="mt-2 text-center text-sm text-slate-500 dark:text-slate-400">No category data yet.</p>
        @endif
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm p-4 sm:p-6">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Exam Attempts (Last 7 Days)</h3>
        <div class="relative h-56 sm:h-64 w-full">
            <canvas id="examAttemptsChart"></canvas>
        </div>
    </div>
</div>

{{-- Recent data --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-800 dark:text-slate-100">Recent Members</h2>
            <a href="{{ route('admin.candidates.index') }}" class="text-sm font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">View all</a>
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800 flex-1">
            @forelse ($stats['recent_members'] as $member)
            <li class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 font-bold text-sm">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $member->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $member->email }}</p>
                    </div>
                </div>
                <span class="text-xs font-medium text-slate-400 shrink-0">{{ $member->created_at?->diffForHumans() }}</span>
            </li>
            @empty
            <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No members found.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-800 dark:text-slate-100">Recent Exams</h2>
            <a href="{{ route('admin.exams.index') }}" class="text-sm font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">View all</a>
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800 flex-1">
            @forelse ($stats['recent_exams'] as $exam)
            @php
                $statusDot = match ($exam->status) {
                    'published', 'active' => 'bg-green-500',
                    'draft' => 'bg-amber-500',
                    'suspended' => 'bg-rose-500',
                    default => 'bg-slate-500',
                };
            @endphp
            <li class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <a href="{{ route('admin.exams.show', $exam) }}" class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $exam->title }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-block h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ $exam->status }}</p>
                    </div>
                </a>
                <div class="text-right shrink-0">
                    <p class="text-xs font-medium text-slate-900 dark:text-white">{{ $exam->duration }} mins</p>
                    <p class="text-[10px] text-slate-400 mt-1">Pass {{ $exam->pass_percentage }}%</p>
                </div>
            </li>
            @empty
            <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No exams yet. <a href="{{ route('admin.exams.create') }}" class="text-indigo-600 dark:text-indigo-400 font-medium">Create one</a></li>
            @endforelse
        </ul>
    </div>
</div>

{{-- Quick Actions --}}
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm p-4 sm:p-6 mb-8">
    <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4 sm:mb-6">Quick Actions</h3>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition shadow-sm bg-sky-500 hover:bg-sky-600 text-white">
            Visit Site
        </a>
        <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition shadow-sm border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-500/10">
            Add Question
        </a>
        <a href="{{ route('admin.exams.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition shadow-sm border border-rose-200 text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-500/10">
            Create Exam
        </a>
        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition shadow-sm border border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
            Settings / Clear Cache
        </a>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#94a3b8' : '#64748b';
        const gridColor = isDark ? '#1e293b' : '#f1f5f9';
        const categoryChart = @json($stats['category_chart']);
        const attemptsChart = @json($stats['attempts_chart']);

        const ctx1 = document.getElementById('categoryQuestionsChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: categoryChart.labels.length ? categoryChart.labels : ['No data'],
                    datasets: [{
                        label: 'Questions',
                        data: categoryChart.values.length ? categoryChart.values : [0],
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, maxRotation: 45, minRotation: 0 } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('examAttemptsChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: attemptsChart.labels,
                    datasets: [{
                        label: 'Attempts',
                        data: attemptsChart.values,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgba(16, 185, 129, 1)',
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, precision: 0 } }
                    }
                }
            });
        }
    });
</script>
@endpush
