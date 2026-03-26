@extends('layouts.app')

@section('title', 'Editor Dashboard — ExamMS')
@section('page-title', 'Content Editor Dashboard')

@section('header-actions')
    <a href="{{ route('editor.questions.create') }}"
       class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Question
    </a>
@endsection

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    @php
    $cards = [
        ['label' => 'My Questions',    'value' => $stats['my_questions'],    'desc' => 'authored by you'],
        ['label' => 'My Exams',        'value' => $stats['my_exams'],        'desc' => 'created by you'],
        ['label' => 'Total Questions', 'value' => $stats['total_questions'], 'desc' => 'in this org'],
        ['label' => 'Draft Exams',     'value' => $stats['draft_exams'],     'desc' => 'awaiting publish'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
        <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">{{ number_format($card['value']) }}</p>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $card['label'] }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $card['desc'] }}</p>
    </div>
    @endforeach
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Quick Actions</h2>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('editor.questions.create') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 px-4 py-2 rounded-lg transition-colors">
            Add Question
        </a>
        <a href="{{ route('editor.questions.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-4 py-2 rounded-lg transition-colors">
            Browse Questions
        </a>
    </div>
</div>

@endsection
