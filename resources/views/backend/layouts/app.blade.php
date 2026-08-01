@extends('backend.layouts.base')

@php
    $brandName = (string) site_setting('brand.logo_text', site_setting('brand.site_name', 'Examtube'));
    $brandName = trim(str_ireplace(['.in', ' Admin'], '', $brandName)) ?: 'Examtube';

    $userName = auth()->user()->name ?? 'User';
    $adminAvatar = user_avatar(auth()->user(), $userName);
    $userInitials = $adminAvatar['initials'];
    $userAvatarUrl = $adminAvatar['url'];
    $userAvatarColor = $adminAvatar['color'];

    $sidebarCollapsed = ($sidebarCollapsedSetting ?? false) ? '1' : '0';
@endphp

@section('body')

<div id="panel-root"
     class="flex h-screen overflow-hidden bg-slate-100 dark:bg-slate-950"
     data-sidebar-collapsed="{{ $sidebarCollapsed }}"
     data-sidebar-open="0">

    <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/50 lg:hidden"></div>

    <aside id="app-sidebar"
           class="fixed inset-y-0 left-0 z-50 flex h-full -translate-x-full flex-col transition-all duration-300 lg:static lg:translate-x-0 w-72">
        <div id="sidebar-logo-container" class="panel-shell-header flex items-center gap-3 border-b px-4 shrink-0 transition-all duration-300">
            <a href="{{ route('admin.dashboard') }}"
               id="sidebar-brand"
               class="sidebar-brand flex min-w-0 items-center gap-3 rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-teal-600/40 dark:focus-visible:ring-teal-400/40"
               title="{{ $brandName }} Admin">
                <span id="sidebar-logo-mark" class="sidebar-brand__mark shrink-0" aria-hidden="true">
                    <img src="{{ asset('images/brand/admin-mark.svg') }}"
                         alt=""
                         width="36"
                         height="36"
                         decoding="async">
                </span>
                <span class="sidebar-brand__copy min-w-0" data-sidebar-label>
                    <span class="sidebar-brand__name block truncate text-sm font-bold tracking-tight text-slate-900 dark:text-white">{{ $brandName }}</span>
                    <span class="sidebar-brand__tag block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Admin console</span>
                </span>
            </a>
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
                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold uppercase text-white shadow-sm"
                     style="background: {{ $userAvatarColor }}">
                    @if($userAvatarUrl)
                        <img src="{{ $userAvatarUrl }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ $userInitials }}
                    @endif
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
                            class="panel-icon-btn relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white p-0 text-slate-600 transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-teal-600/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white"
                            aria-label="Toggle sidebar">
                        <svg data-sidebar-toggle-icon class="block h-5 w-5 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <p class="truncate text-base sm:text-lg font-semibold text-slate-950 dark:text-white">@yield('page-title', 'Dashboard')</p>
                        <p class="truncate text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 opacity-80 sm:opacity-100">{{ $brandName }} Admin</p>
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
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="min-w-0 text-sm font-medium text-slate-500 dark:text-slate-400">
                            @yield('breadcrumbs')
                        </div>
                        <a href="{{ route('home') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-sky-500 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-sky-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Visit Site
                        </a>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>

@include('backend.partials.image-editor-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/panel-shell.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/gallery-editor.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js" defer></script>
    <script src="{{ versioned_asset('js/backend/gallery-editor.js') }}" defer></script>
@endpush
