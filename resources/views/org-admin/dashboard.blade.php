@extends('layouts.app')

@section('title', 'Org Admin Dashboard — ExamMS')
@section('page-title', 'Organization Dashboard')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    @php
    $cards = [
        ['label' => 'Members',       'value' => $stats['total_members'],   'color' => 'blue'],
        ['label' => 'Total Exams',   'value' => $stats['total_exams'],     'color' => 'green'],
        ['label' => 'Published',     'value' => $stats['published_exams'], 'color' => 'teal'],
        ['label' => 'Questions',     'value' => $stats['total_questions'], 'color' => 'amber'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
        <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($card['value']) }}</p>
        <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
    </div>
    @endforeach
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent Exams</h2>
        <a href="{{ route('org-admin.exams.index') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Manage Exams →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($stats['recent_exams'] as $exam)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $exam->title }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $exam->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($exam->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $exam->duration }} min</td>
                    <td class="px-6 py-3 text-gray-400 text-xs">{{ $exam->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">No exams created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
