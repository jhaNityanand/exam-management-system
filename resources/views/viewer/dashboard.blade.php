@extends('layouts.app')

@section('title', 'My Dashboard — ExamMS')
@section('page-title', 'My Exam Dashboard')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    @php
    $cards = [
        ['label' => 'Available Exams', 'value' => $stats['available_exams'],  'color' => 'violet'],
        ['label' => 'My Attempts',     'value' => $stats['my_attempts'],      'color' => 'blue'],
        ['label' => 'Passed',          'value' => $stats['passed_attempts'],  'color' => 'green'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
        <p class="text-3xl font-bold text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400 mb-1">{{ number_format($card['value']) }}</p>
        <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- CTA --}}
<div class="mb-6">
    <a href="{{ route('viewer.exams.index') }}"
       class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-medium px-5 py-2.5 rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Browse Available Exams
    </a>
</div>

{{-- Recent Attempts --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent Attempts</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($stats['recent_attempts'] as $attempt)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $attempt->exam->title ?? '—' }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ $attempt->score }}%</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $attempt->passed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                            {{ $attempt->passed ? 'Passed' : 'Failed' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-400 text-xs">{{ $attempt->submitted_at?->diffForHumans() ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">You haven't taken any exams yet. Start one now!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
