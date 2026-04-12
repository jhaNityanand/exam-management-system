@extends('backend.layouts.app')

@section('title', $organization->name)
@section('page-title', $organization->name)

@section('header-actions')
    <a href="{{ route('admin.organizations.edit', $organization) }}" class="text-sm font-medium text-indigo-600">Edit</a>
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 space-y-2 text-sm">
        <p><span class="text-gray-500">Slug:</span> <span class="font-mono">{{ $organization->slug }}</span></p>
        <p><span class="text-gray-500">Status:</span> {{ $organization->status }}</p>
        <p class="text-gray-600 dark:text-gray-300">{{ $organization->description ?: '—' }}</p>
        <p><span class="text-gray-500">Members:</span> {{ $organization->users_count }}</p>
        <p><span class="text-gray-500">Exams:</span> {{ $organization->exams_count }}</p>
    </div>
</div>
@endsection
