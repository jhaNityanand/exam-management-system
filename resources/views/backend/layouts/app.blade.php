@extends('backend.layouts.base')

@php
    $appName = config('app.name', 'ExamMS');
    $appInitials = str($appName)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => str($part)->substr(0, 1))
        ->join('');

    $userName = auth()->user()->name ?? 'User';
    $nameParts = explode(' ', trim($userName));
    if (count($nameParts) >= 2) {
        $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $userInitials = strtoupper(substr($userName, 0, 2));
    }

    $sidebarCollapsed = ($sidebarCollapsedSetting ?? false) ? '1' : '0';
@endphp

@section('body')
<style>
    html, body {
        height: 100%;
        overflow: hidden;
    }

    #panel-root {
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
    }

    #panel-content {
        min-height: 0;
    }

    #panel-main {
        min-height: 0;
        overflow-y: auto;
    }

    /* Precision Sidebar Transitions */
    #app-sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    /* Hide scrollbar but keep functionality */
    #app-sidebar nav::-webkit-scrollbar { display: none; }
    #app-sidebar nav { -ms-overflow-style: none; scrollbar-width: none; }
    
    [data-sidebar-collapsed="1"] #app-sidebar { width: 80px !important; }
    [data-sidebar-collapsed="1"] #sidebar-logo-container { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
    [data-sidebar-collapsed="1"] #sidebar-avatar-container { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
    [data-sidebar-collapsed="1"] [data-sidebar-label] { display: none !important; }
    [data-sidebar-collapsed="1"] [data-sidebar-avatar-details] { display: none !important; }
    [data-sidebar-collapsed="1"] .sidebar-link { justify-content: center; padding-left: 0; padding-right: 0; gap: 0; }
    [data-sidebar-collapsed="1"] .sidebar-link svg { margin-right: 0; }
</style>

<div id="panel-root"
     class="flex h-screen overflow-hidden bg-slate-100 dark:bg-slate-950"
     data-sidebar-collapsed="{{ $sidebarCollapsed }}"
     data-sidebar-open="0">

    <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden"></div>

    <aside id="app-sidebar"
           class="fixed inset-y-0 left-0 z-50 flex h-full -translate-x-full flex-col bg-slate-950 text-white transition-all duration-300 lg:static lg:translate-x-0 w-72">
        <div id="sidebar-logo-container" class="flex items-center gap-3 border-b border-white/10 px-4 h-16 shrink-0 transition-all duration-300">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-sm font-bold uppercase tracking-[0.25em] text-white shrink-0">
                {{ $appInitials }}
            </div>
            <div class="min-w-0" data-sidebar-label>
                <p class="truncate text-base font-semibold tracking-tight">{{ $appName }}</p>
                <p class="truncate text-xs text-slate-400">Workspace</p>
            </div>
            <button type="button"
                    data-sidebar-close
                    class="ml-auto inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 text-slate-300 transition hover:bg-white/10 hover:text-white lg:hidden">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 flex flex-col space-y-1">
            @include('backend.layouts.partials.sidebar-top-links')
        </nav>

        <div class="shrink-0 flex flex-col px-3 pt-4 border-t border-white/10">
            @include('backend.layouts.partials.sidebar-bottom-links')
        </div>

        <div class="shrink-0 px-3 py-4 border-t border-white/10 mt-4">
            <div id="sidebar-avatar-container" class="flex items-center gap-3 rounded-2xl bg-white/5 px-3 py-3 transition-all duration-300">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-sm font-bold uppercase text-slate-950">
                    {{ $userInitials }}
                </div>
                <div class="min-w-0 flex-1" data-sidebar-avatar-details>
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="panel-content" class="flex min-h-0 min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 shrink-0 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
            <div class="flex items-center justify-between gap-4 px-4 h-16 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button"
                            data-sidebar-toggle
                            class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white"
                            aria-label="Toggle sidebar">
                        <svg data-sidebar-toggle-icon class="h-5 w-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="truncate text-base sm:text-lg font-semibold text-slate-950 dark:text-white">@yield('page-title', 'Dashboard')</p>
                        <p class="truncate text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 opacity-80 sm:opacity-100">{{ $appName }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @yield('header-actions')
                    @include('backend.layouts.partials.panel-topbar')
                </div>
            </div>
        </header>

        <main id="panel-main" class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <div class="mx-auto flex w-full flex-col gap-4 sm:gap-6 @yield('content-container-class', 'max-w-7xl')">
                @hasSection('breadcrumbs')
                    <nav class="flex text-sm text-slate-500 dark:text-slate-400 font-medium mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2">
                            @yield('breadcrumbs')
                        </ol>
                    </nav>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>
@endsection
