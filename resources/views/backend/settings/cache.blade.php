@extends('backend.layouts.app')

@section('title', 'Cache & Optimization')
@section('page-title', 'Cache & Optimization')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Cache & Optimization'],
    ]" />
@endsection

@section('content')
@php
    $actionIcons = [
        'clear_app_cache' => ['M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
        'clear_config_cache' => ['M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        'clear_route_cache' => ['M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
        'clear_view_cache' => ['M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
        'clear_event_cache' => ['M13 10V3L4 14h7v7l9-11h-7z'],
        'optimize' => ['M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
        'optimize_clear' => ['M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
        'storage_link' => ['M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
        'clear_temp' => ['M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'],
        'clear_logs' => ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'regenerate_sitemap' => ['M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'],
        'import_legacy_examtube' => ['M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
        'migrate_fresh_seed' => ['M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
        'db_seed' => ['M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
    ];

    $iconThemes = [
        'clear_app_cache' => 'indigo',
        'clear_config_cache' => 'violet',
        'clear_route_cache' => 'sky',
        'clear_view_cache' => 'cyan',
        'clear_event_cache' => 'amber',
        'optimize' => 'emerald',
        'optimize_clear' => 'teal',
        'storage_link' => 'blue',
        'clear_temp' => 'orange',
        'clear_logs' => 'rose',
        'regenerate_sitemap' => 'fuchsia',
        'import_legacy_examtube' => 'violet',
        'migrate_fresh_seed' => 'rose',
        'db_seed' => 'emerald',
    ];
@endphp

<div class="space-y-6">
    <x-page-card>
        <div class="border-b border-slate-200/80 px-4 py-2 sm:px-3 dark:border-slate-800">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Cache &amp; Optimization</h1>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400 max-w-3xl">
                Run Laravel optimization commands from the admin panel when SSH/CLI access is unavailable.
                Each action shows the command output below.
            </p>
        </div>

        <div class="px-5 py-6 sm:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-5" id="cache-action-grid">
                @foreach($actions as $action)
                    @php
                        $danger = !empty($action['danger']);
                        $paths = $actionIcons[$action['key']] ?? ['M13 10V3L4 14h7v7l9-11h-7z'];
                        $theme = $iconThemes[$action['key']] ?? ($danger ? 'rose' : 'indigo');
                    @endphp
                    <div class="group flex h-full min-h-[12rem] flex-col rounded-2xl border border-slate-200 bg-white p-2 sm:p-4 transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/60 dark:hover:bg-slate-900 {{ $danger ? 'hover:border-rose-300 dark:hover:border-rose-500/40' : 'hover:border-indigo-300 dark:hover:border-indigo-500/50' }}">
                        <div class="flex items-start gap-4">
                            <span class="cache-action-icon cache-action-icon--{{ $theme }} inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl transition duration-200 group-hover:scale-105" aria-hidden="true">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @foreach($paths as $path)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}"/>
                                    @endforeach
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1 pt-0.5">
                                <h2 class="text-[0.95rem] font-semibold leading-snug text-slate-900 dark:text-white">{{ $action['label'] }}</h2>
                                <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $action['description'] }}</p>
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4 mt-5 dark:border-slate-800">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $danger
                                ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                                : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                {{ $danger ? 'Destructive' : 'Safe action' }}
                            </span>
                            <button
                                type="button"
                                class="cache-action-btn inline-flex min-h-[2.5rem] min-w-[5.75rem] items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 {{ $danger
                                    ? 'bg-rose-600 text-white hover:bg-rose-500 focus:ring-rose-500/40'
                                    : 'bg-indigo-600 text-white hover:bg-indigo-500 focus:ring-indigo-500/40' }}"
                                data-action="{{ $action['key'] }}"
                                data-label="{{ $action['label'] }}"
                                data-confirm="{{ $action['confirm'] ?? '' }}"
                                data-danger="{{ $danger ? '1' : '0' }}"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="cache-action-btn__label">Run</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-page-card>

    <x-page-card>
        <div class="px-5 py-6 sm:px-6 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Execution results</h2>
                <button type="button" id="cache-results-clear" class="panel-button-secondary text-xs" hidden>Clear log</button>
            </div>
            <div id="cache-results-empty" class="rounded-xl border border-dashed border-slate-200 px-5 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                No actions run yet. Click <span class="font-medium text-slate-700 dark:text-slate-300">Run</span> on a card above to execute a command.
            </div>
            <div id="cache-results" class="space-y-3" hidden></div>
        </div>
    </x-page-card>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/settings-cache.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.cacheOptimizationConfig = {
            runUrl: @json(route('admin.settings.cache.run')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/backend/settings-cache.js') }}?v={{ @filemtime(public_path('js/backend/settings-cache.js')) ?: time() }}"></script>
@endpush
