@extends('backend.layouts.app')

@section('title', 'Exams')
@section('page-title', 'Available exams')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($exams as $exam)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5 flex flex-col">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $exam->title }}</h3>
            <p class="text-xs text-gray-500 mb-4 flex-1">{{ $exam->duration }} minutes · Pass {{ $exam->pass_percentage }}%</p>
            <span class="text-xs text-violet-600 font-medium">{{ ucfirst($exam->status) }}</span>
        </div>
    @empty
        <p class="text-gray-500 text-sm col-span-full">No published exams for this organization.</p>
    @endforelse
</div>
@endsection
