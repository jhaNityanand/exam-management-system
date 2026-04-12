@extends('backend.layouts.app')

@section('title', 'Question')
@section('page-title', 'Question #'.$question->id)

@section('header-actions')
    @orgCan('question.update')
        <a href="{{ route('admin.questions.edit', $question) }}" class="text-sm text-emerald-600 font-medium">Edit</a>
    @endorgCan
@endsection

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 space-y-4 text-sm">
    <div class="prose dark:prose-invert max-w-none">{!! $question->body !!}</div>
    <p><span class="text-gray-500">Type:</span> {{ $question->type }}</p>
    <p><span class="text-gray-500">Difficulty:</span> {{ $question->difficulty }}</p>
    <p><span class="text-gray-500">Marks:</span> {{ $question->marks }}</p>
    @if($question->options)
        <div>
            <p class="text-gray-500 mb-1">Options</p>
            <ul class="list-disc pl-5">
                @foreach ($question->options as $opt)
                    <li>{{ $opt['text'] ?? json_encode($opt) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection

