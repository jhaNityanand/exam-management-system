@extends('backend.layouts.app')

@section('title', 'Coming Soon')
@section('page-title', 'Work in Progress')

@section('breadcrumbs')
    <li class="inline-flex items-center">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition">Admin</a>
    </li>
    <li>
        <div class="flex items-center">
            <span class="mx-2">/</span>
            <span class="text-slate-800 dark:text-slate-200">Coming Soon</span>
        </div>
    </li>
@endsection

@section('content')
<div class="flex flex-col items-center justify-center py-20 px-4 text-center">
    <div class="mb-6 rounded-full bg-indigo-50 dark:bg-indigo-500/10 p-6 flex items-center justify-center">
        <svg class="h-16 w-16 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
    </div>
    <h2 class="mb-2 text-2xl font-bold text-slate-900 dark:text-white md:text-3xl">Coming Soon!</h2>
    <p class="mb-8 max-w-md text-slate-500 dark:text-slate-400">
        We're still working on this module. Check back later to see the new features and improvements.
    </p>
    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
        Back to Dashboard
    </a>
</div>
@endsection
