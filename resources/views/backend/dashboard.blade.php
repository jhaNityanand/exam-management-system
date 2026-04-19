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

{{-- ── Section 1: Summary Cards ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    @php
    $cards = [
        ['label' => 'Total Questions',  'value' => '1,452', 'color' => 'blue',   'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'trend' => '+12%'],
        ['label' => 'Total Categories', 'value' => '34',    'color' => 'indigo', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'trend' => '+2%'],
        ['label' => 'Total Candidates', 'value' => '892',   'color' => 'emerald','icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'trend' => '+24%'],
        ['label' => 'Active Exams',     'value' => '12',    'color' => 'rose',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'trend' => '+5%'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-6 shadow-sm transition hover:shadow-md hover:border-{{ $card['color'] }}-200 dark:hover:border-{{ $card['color'] }}-800 group">
        <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br from-{{ $card['color'] }}-100 to-{{ $card['color'] }}-50 dark:from-{{ $card['color'] }}-900/40 dark:to-{{ $card['color'] }}-900/10 opacity-50 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $card['value'] }}</p>
                    <span class="text-xs font-semibold text-green-500 bg-green-50 dark:bg-green-500/10 px-2 py-0.5 rounded-full">{{ $card['trend'] }}</span>
                </div>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-500/10 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 shadow-inner">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                </svg>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Section 2: Charts ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm p-6">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Category-wise Questions</h3>
        <div class="relative h-64 w-full">
            <canvas id="categoryQuestionsChart"></canvas>
        </div>
    </div>
    
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm p-6">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-4">Candidate Exam Attempts</h3>
        <div class="relative h-64 w-full">
            <canvas id="examAttemptsChart"></canvas>
        </div>
    </div>
</div>

{{-- ── Section 3: Recent Data ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Recent Candidates --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-800 dark:text-slate-100">Recent Candidates</h2>
            <a href="#" class="text-sm font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">View all</a>
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800 flex-1">
            @foreach(['Alice Johnson', 'Bob Smith', 'Charlie Davis', 'Diana Prince'] as $name)
            <li class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 flex-shrink-0 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 font-bold text-sm">
                        {{ substr($name, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ strtolower(str_replace(' ', '.', $name)) }}@example.com</p>
                    </div>
                </div>
                <span class="text-xs font-medium text-slate-400">2 hours ago</span>
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Recent Exams --}}
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="font-semibold text-slate-800 dark:text-slate-100">Recent Exams</h2>
            <a href="#" class="text-sm font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">View all</a>
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800 flex-1">
            @foreach([
                ['title' => 'Midterm Mathematics', 'status' => 'Published', 'color' => 'green'],
                ['title' => 'Weekly Quiz: Chemistry', 'status' => 'Draft', 'color' => 'amber'],
                ['title' => 'Final Physics Assessment', 'status' => 'Published', 'color' => 'green'],
                ['title' => 'Computer Science Basics', 'status' => 'Archived', 'color' => 'slate']
            ] as $exam)
            <li class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $exam['title'] }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-{{ $exam['color'] }}-500"></span>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $exam['status'] }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium text-slate-900 dark:text-white">45 mins</p>
                    <p class="text-[10px] text-slate-400 mt-1">100 marks</p>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
</div>

{{-- ── Section 4: Quick Actions ──────────────────────────────────────────────────────────── --}}
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm p-6 mb-8">
    <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-6">System Utilities & Quick Actions</h3>
    
    <div class="flex flex-wrap gap-3">
        @php
            $actions = [
                ['label' => 'Visit Site', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'color' => 'bg-sky-500 hover:bg-sky-600 text-white border-transparent'],
                ['label' => 'RSS Feed', 'icon' => 'M6 5c7.18 0 13 5.82 13 13M6 11a7 7 0 017 7m-6 0a1 1 0 11-2 0 1 1 0 012 0z', 'color' => 'bg-sky-400 hover:bg-sky-500 text-white border-transparent'],
                ['label' => 'Sitemap', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'color' => 'bg-sky-400 hover:bg-sky-500 text-white border-transparent'],
                ['label' => 'Clear Cache', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'color' => 'bg-transparent hover:bg-emerald-50 dark:hover:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800 border'],
                ['label' => 'Clear Routes', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'bg-transparent hover:bg-amber-50 dark:hover:bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800 border'],
                ['label' => 'Clear Views', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 'color' => 'bg-transparent hover:bg-rose-50 dark:hover:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800 border'],
                ['label' => 'Config Cache', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'color' => 'bg-transparent hover:bg-purple-50 dark:hover:bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800 border'],
                ['label' => 'Cache Routes', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', 'color' => 'bg-transparent hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 border'],
                ['label' => 'Optimize', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'bg-transparent hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 border'],
                ['label' => 'Optimize Clear', 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', 'color' => 'bg-transparent hover:bg-teal-50 dark:hover:bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-200 dark:border-teal-800 border']
            ];
        @endphp

        @foreach($actions as $action)
        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition shadow-sm {{ $action['color'] }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/>
            </svg>
            {{ $action['label'] }}
        </button>
        @endforeach
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

        // Chart 1: Category-wise Questions
        const ctx1 = document.getElementById('categoryQuestionsChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['Mathematics', 'Science', 'English', 'History', 'Geography', 'Computer'],
                    datasets: [{
                        label: 'Questions',
                        data: [120, 150, 80, 90, 70, 110],
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor } },
                        y: { grid: { color: gridColor }, ticks: { color: textColor } }
                    }
                }
            });
        }

        // Chart 2: Candidate Exam Attempts
        const ctx2 = document.getElementById('examAttemptsChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Attempts',
                        data: [45, 52, 38, 65, 80, 110, 95],
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
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor } },
                        y: { grid: { color: gridColor }, ticks: { color: textColor } }
                    }
                }
            });
        }
    });
</script>
@endpush
