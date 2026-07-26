@extends('backend.layouts.app')

@section('title', 'Cache & Optimization')
@section('page-title', 'Cache & Optimization')
@section('content-container-class', 'max-w-5xl')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Cache & Optimization'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <x-page-card>
        <div class="px-4 py-5 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Cache &amp; Optimization</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Run Laravel optimization commands from the admin panel when SSH/CLI access is unavailable.
                Each action shows the command output below.
            </p>
        </div>

        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="cache-action-grid">
                @foreach($actions as $action)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col gap-3 bg-white dark:bg-slate-900/40">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $action['label'] }}</h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ $action['description'] }}</p>
                        </div>
                        <button
                            type="button"
                            class="cache-action-btn panel-button-{{ !empty($action['danger']) ? 'secondary' : 'primary' }} text-sm w-full sm:w-auto self-start {{ !empty($action['danger']) ? 'text-red-600 dark:text-red-400' : '' }}"
                            data-action="{{ $action['key'] }}"
                            data-label="{{ $action['label'] }}"
                            data-confirm="{{ $action['confirm'] ?? '' }}"
                            data-danger="{{ !empty($action['danger']) ? '1' : '0' }}"
                        >
                            Run
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </x-page-card>

    <x-page-card>
        <div class="px-4 py-5 sm:px-6 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Execution results</h2>
                <button type="button" id="cache-results-clear" class="panel-button-secondary text-xs" hidden>Clear log</button>
            </div>
            <div id="cache-results-empty" class="text-sm text-slate-500 dark:text-slate-400">
                No actions run yet. Click a button above to execute a command.
            </div>
            <div id="cache-results" class="space-y-3" hidden></div>
        </div>
    </x-page-card>
</div>
@endsection

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
