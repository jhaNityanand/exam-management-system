@extends('layouts.base')

@section('body')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-lg w-full bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No organization access</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Your account is not linked to an organization yet. Ask a super administrator to assign you to an organization with a role.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('profile.edit') }}" class="inline-flex justify-center rounded-lg bg-indigo-600 text-white text-sm font-medium px-4 py-2 hover:bg-indigo-700">Edit profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">Log out</button>
            </form>
        </div>
    </div>
</div>
@endsection
