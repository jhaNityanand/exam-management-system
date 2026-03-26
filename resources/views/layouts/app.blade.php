@extends('layouts.base')

@php
    $appName = config('app.name', 'ExamMS');
    $appInitials = str($appName)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => str($part)->substr(0, 1))
        ->join('');

    $userInitials = str(auth()->user()->name ?? 'U')
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => str($part)->substr(0, 1))
        ->join('');

    $sidebarCollapsed = ($sidebarCollapsedSetting ?? false) ? '1' : '0';
@endphp

@section('body')
<div id="panel-root"
     class="flex min-h-screen bg-slate-100 dark:bg-slate-950"
     data-sidebar-collapsed="{{ $sidebarCollapsed }}"
     data-sidebar-open="0">

    <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden"></div>

    <aside id="app-sidebar"
           class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-slate-950 text-white transition duration-300 lg:static lg:translate-x-0">
        <div class="flex items-center gap-3 border-b border-white/10 px-4 py-5">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-sm font-bold uppercase tracking-[0.25em] text-white">
                {{ $appInitials }}
            </div>
            <div class="min-w-0" data-sidebar-label>
                <p class="truncate text-base font-semibold tracking-tight">{{ $appName }}</p>
                <p class="truncate text-xs text-slate-400">Exam management workspace</p>
            </div>
            <button type="button"
                    data-sidebar-close
                    class="ml-auto inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 text-slate-300 transition hover:bg-white/10 hover:text-white lg:hidden">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <div class="space-y-1">
                @include('layouts.partials.sidebar-links')
            </div>
        </nav>

        <div class="border-t border-white/10 px-3 py-4">
            <div class="flex items-center gap-3 rounded-2xl bg-white/5 px-3 py-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-sm font-bold uppercase text-slate-950">
                    {{ $userInitials }}
                </div>
                <div class="min-w-0 flex-1" data-sidebar-avatar-details>
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
            <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button"
                            data-sidebar-toggle
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="truncate text-lg font-semibold text-slate-950 dark:text-white">@yield('page-title', 'Dashboard')</p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $currentOrgModel?->name ?? $appName }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @yield('header-actions')
                    @include('layouts.partials.panel-topbar')
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 sm:gap-6">
                @hasSection('breadcrumbs')
                    <div class="panel-card">
                        <div class="panel-card-body py-4">
                            @yield('breadcrumbs')
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>
@endsection
