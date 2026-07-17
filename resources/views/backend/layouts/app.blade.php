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
    :root {
        --panel-header-height: 4rem;
    }

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

    .panel-shell-header {
        box-sizing: border-box;
        height: var(--panel-header-height);
        min-height: var(--panel-header-height);
        max-height: var(--panel-header-height);
    }

    /* Sidebar shell and navigation */
    #app-sidebar {
        color: #334155;
        background:
            radial-gradient(circle at 10% 0%, rgb(99 102 241 / 0.08), transparent 19rem),
            #ffffff;
        border-right: 1px solid #dbe3ef;
        box-shadow: 10px 0 30px rgb(15 23 42 / 0.06);
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dark #app-sidebar {
        color: #cbd5e1;
        background:
            radial-gradient(circle at 10% 0%, rgb(99 102 241 / 0.14), transparent 20rem),
            #0f172a;
        border-right-color: #263449;
        box-shadow: 12px 0 36px rgb(0 0 0 / 0.28);
    }

    #sidebar-logo-container,
    #app-sidebar > nav,
    #app-sidebar > div {
        position: relative;
        z-index: 1;
    }

    #sidebar-logo-container {
        border-bottom-color: #e2e8f0;
    }

    .dark #sidebar-logo-container {
        border-bottom-color: #263449;
    }

    #sidebar-logo-mark {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        box-shadow: 0 8px 20px rgb(79 70 229 / 0.24);
    }

    #app-sidebar .sidebar-link {
        color: #526176;
        border: 1px solid transparent;
        border-radius: 0.75rem;
        font-weight: 550;
    }

    #app-sidebar .sidebar-link:hover {
        color: #1e293b;
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    #app-sidebar .sidebar-link[data-active="true"] {
        color: #4338ca;
        background: #eef2ff;
        border-color: #c7d2fe;
        box-shadow: 0 5px 14px rgb(79 70 229 / 0.1);
    }

    .dark #app-sidebar .sidebar-link {
        color: #aebbd0;
    }

    .dark #app-sidebar .sidebar-link:hover {
        color: #f8fafc;
        background: rgb(51 65 85 / 0.7);
        border-color: #3b4a61;
    }

    .dark #app-sidebar .sidebar-link[data-active="true"] {
        color: #e0e7ff;
        background: linear-gradient(90deg, rgb(79 70 229 / 0.35), rgb(99 102 241 / 0.17));
        border-color: rgb(129 140 248 / 0.45);
        box-shadow: inset 3px 0 0 #818cf8, 0 8px 22px rgb(0 0 0 / 0.16);
    }

    #app-sidebar .sidebar-child-link {
        color: #64748b;
        border-radius: 0.625rem;
    }

    #app-sidebar .sidebar-child-link:hover {
        color: #4338ca;
        background: #f1f5f9;
    }

    #app-sidebar .sidebar-child-link[data-active="true"] {
        color: #4338ca;
        background: #eef2ff;
        font-weight: 700;
    }

    .dark #app-sidebar .sidebar-child-link {
        color: #8492a8;
    }

    .dark #app-sidebar .sidebar-child-link:hover {
        color: #e2e8f0;
        background: rgb(51 65 85 / 0.5);
    }

    .dark #app-sidebar .sidebar-child-link[data-active="true"] {
        color: #c7d2fe;
        background: rgb(79 70 229 / 0.18);
    }

    #sidebar-secondary-links,
    #sidebar-user-section {
        border-top-color: #e2e8f0;
    }

    .dark #sidebar-secondary-links,
    .dark #sidebar-user-section {
        border-top-color: #263449;
    }

    #sidebar-avatar-container {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .dark #sidebar-avatar-container {
        background: rgb(30 41 59 / 0.8);
        border-color: #334155;
    }

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
           class="fixed inset-y-0 left-0 z-50 flex h-full -translate-x-full flex-col transition-all duration-300 lg:static lg:translate-x-0 w-72">
        <div id="sidebar-logo-container" class="panel-shell-header flex items-center gap-3 border-b px-4 shrink-0 transition-all duration-300">
            <div id="sidebar-logo-mark" class="flex h-9 w-9 items-center justify-center rounded-xl text-sm font-bold uppercase tracking-[0.18em] shrink-0">
                {{ $appInitials }}
            </div>
            <div class="min-w-0" data-sidebar-label>
                <p class="truncate text-sm font-bold tracking-tight text-slate-900 dark:text-white">{{ $appName }}</p>
                <p class="truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Exam Management System</p>
            </div>
            <button type="button"
                    data-sidebar-close
                    class="ml-auto inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white lg:hidden">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 flex flex-col space-y-1">
            @include('backend.layouts.partials.sidebar-top-links')
        </nav>

        <div id="sidebar-secondary-links" class="shrink-0 flex flex-col px-3 pt-4 border-t">
            @include('backend.layouts.partials.sidebar-bottom-links')
        </div>

        <div id="sidebar-user-section" class="shrink-0 px-3 py-4 border-t mt-4">
            <div id="sidebar-avatar-container" class="flex items-center gap-3 rounded-xl px-3 py-3 transition-all duration-300">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold uppercase text-white shadow-sm">
                    {{ $userInitials }}
                </div>
                <div class="min-w-0 flex-1" data-sidebar-avatar-details>
                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    <div id="panel-content" class="flex min-h-0 min-w-0 flex-1 flex-col">
        <header class="panel-shell-header sticky top-0 z-30 shrink-0 border-b border-slate-200/80 bg-white/95 shadow-sm shadow-slate-200/30 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 dark:shadow-black/10">
            <div class="panel-shell-header flex items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button"
                            data-sidebar-toggle
                            class="panel-icon-btn relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white p-0 text-slate-600 transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white"
                            aria-label="Toggle sidebar">
                        <svg data-sidebar-toggle-icon class="block h-5 w-5 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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

@include('backend.partials.image-editor-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/gallery-editor.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js" defer></script>
    <script src="{{ versioned_asset('js/backend/gallery-editor.js') }}" defer></script>
@endpush
