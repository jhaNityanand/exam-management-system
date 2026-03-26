@extends('layouts.app')

@section('title', 'System settings')
@section('page-title', 'System settings')

@section('content')
<div class="max-w-xl bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 space-y-4">
    <p class="text-sm text-gray-600 dark:text-gray-400">Clear compiled views, config, and route cache after configuration changes.</p>
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="clear-cache">
        <button type="submit" class="bg-gray-900 dark:bg-gray-100 dark:text-gray-900 text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90">
            Clear application cache
        </button>
    </form>
</div>
@endsection
