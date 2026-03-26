@extends('layouts.app')

@section('title', 'User roles')
@section('page-title', 'Roles: '.$user->name)

@section('content')
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-xl space-y-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6">
    @csrf
    @method('PUT')
    <p class="text-sm text-gray-600 dark:text-gray-400">Only the global <strong>admin</strong> role is managed here. Organization roles are set per organization (org admin → Members).</p>
    <div class="space-y-2">
        @foreach ($roles as $role)
            <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                       @checked($user->hasRole($role->name)) class="rounded border-gray-300">
                {{ $role->name }}
            </label>
        @endforeach
    </div>
    @error('roles')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-indigo-700">Save</button>
</form>
@endsection
