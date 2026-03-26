@extends('layouts.app')

@section('title', $user->name)
@section('page-title', $user->name)

@section('header-actions')
    <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-indigo-600">Edit roles</a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 text-sm space-y-2">
        <p><span class="text-gray-500">Email:</span> {{ $user->email }}</p>
        <p><span class="text-gray-500">Global roles:</span> {{ $user->roles->pluck('name')->join(', ') ?: 'None' }}</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="font-medium mb-3">Organizations</h3>
        <ul class="text-sm space-y-2">
            @forelse ($user->organizations as $org)
                <li>{{ $org->name }} <span class="text-gray-400">({{ $org->pivot->role }})</span></li>
            @empty
                <li class="text-gray-400">None</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
