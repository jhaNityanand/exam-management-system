@extends('layouts.app')

@section('title', $exam->title)
@section('page-title', $exam->title)

@section('header-actions')
    @orgCan('exam.publish')
        @if($exam->status !== 'published')
            <form action="{{ route('workspace.exams.publish', $exam) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="text-sm font-medium bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700">Publish</button>
            </form>
        @endif
    @endorgCan
    @orgCan('exam.update')
        <a href="{{ route('workspace.exams.edit', $exam) }}" class="text-sm font-medium text-blue-600">Edit</a>
    @endorgCan
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 prose dark:prose-invert max-w-none text-sm">
            {!! $exam->description ?: '<p class="text-gray-400">No description.</p>' !!}
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 font-medium">Questions ({{ $exam->questions->count() }})</div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                @forelse ($exam->questions as $q)
                    <li class="px-6 py-3 text-sm">
                        <span class="text-gray-400">#{{ $q->id }}</span>
                        <span class="text-gray-900 dark:text-gray-100">{!! \Illuminate\Support\Str::limit($q->body, 120) !!}</span>
                    </li>
                @empty
                    <li class="px-6 py-4 text-sm text-gray-400">No questions linked.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 text-sm space-y-2">
            <p><span class="text-gray-500">Status:</span> {{ $exam->status }}</p>
            <p><span class="text-gray-500">Duration:</span> {{ $exam->duration }} min</p>
            <p><span class="text-gray-500">Pass:</span> {{ $exam->pass_percentage }}%</p>
            <p><span class="text-gray-500">Attempts:</span> {{ $exam->max_attempts }}</p>
            <p><span class="text-gray-500">Negative / Q:</span> {{ $exam->negative_mark_per_question }}</p>
            <p><span class="text-gray-500">Mode:</span> {{ $exam->exam_mode }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 text-sm">
            <h3 class="font-medium mb-2">Attempts</h3>
            <p>Total: {{ $stats['total'] }}</p>
            <p>Passed: {{ $stats['passed'] }}</p>
            <p>Avg score: {{ number_format($stats['avg_score'], 2) }}</p>
        </div>
    </div>
</div>
@endsection

