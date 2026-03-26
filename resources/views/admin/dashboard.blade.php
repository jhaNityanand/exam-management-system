@extends('layouts.app')

@section('title', 'Admin Dashboard — ExamMS')
@section('page-title', 'Super Admin Dashboard')

@section('breadcrumbs')
    <span>Home</span> / <span class="text-gray-700">Dashboard</span>
@endsection

@section('content')

{{-- ── Stat Cards ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    @php
    $cards = [
        ['label' => 'Organizations', 'value' => $stats['total_organizations'],  'color' => 'indigo',  'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        ['label' => 'Total Users',   'value' => $stats['total_users'],           'color' => 'blue',    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ['label' => 'Total Exams',   'value' => $stats['total_exams'],           'color' => 'green',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['label' => 'Questions',     'value' => $stats['total_questions'],       'color' => 'amber',   'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/30 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($card['value']) }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
        </div>
    </div>
    @endforeach

</div>

{{-- ── Recent Data ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Recent Organizations --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent Organizations</h2>
            <a href="{{ route('admin.organizations.index') }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View all →</a>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($stats['recent_organizations'] as $org)
            <li class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-700 dark:text-indigo-300 text-xs font-bold">
                        {{ strtoupper(substr($org->name, 0, 2)) }}
                    </div>
                    <span class="text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $org->name }}</span>
                </div>
                <span class="text-xs {{ $org->status === 'active' ? 'text-green-600 bg-green-50 dark:bg-green-900/30' : 'text-red-500 bg-red-50 dark:bg-red-900/30' }} px-2 py-0.5 rounded-full">
                    {{ ucfirst($org->status) }}
                </span>
            </li>
            @empty
            <li class="px-6 py-4 text-sm text-gray-400">No organizations yet.</li>
            @endforelse
        </ul>
    </div>

    {{-- Recent Users --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent Users</h2>
            <a href="{{ route('admin.users.index') }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View all →</a>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($stats['recent_users'] as $user)
            <li class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-200 text-xs font-bold">
                        {{ str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => str($part)->substr(0, 1))->join('') }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
                <span class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
            </li>
            @empty
            <li class="px-6 py-4 text-sm text-gray-400">No users yet.</li>
            @endforelse
        </ul>
    </div>

</div>

@endsection
