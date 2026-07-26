@extends('backend.layouts.app')

@section('title', 'SEO & Search Engines')
@section('page-title', 'SEO & Search Engines')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'SEO'],
    ]" />
@endsection

@section('content')
@php $s = $settings; @endphp

<x-page-card class="overflow-visible mb-6">
    <div class="px-4 py-5 sm:p-6 space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Generated SEO files</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Sitemap index, robots.txt, RSS/Atom feeds, humans.txt, security.txt, and web app manifest.
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400" id="seo-last-generated">
                    @if(!empty($status['last_generated_at']))
                        Last generated: {{ \Illuminate\Support\Carbon::parse($status['last_generated_at'])->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                    @else
                        Not generated yet — run generation to create public files.
                    @endif
                </p>
            </div>
            <button type="button" id="seo-regenerate-btn" class="panel-button-primary shrink-0">
                Regenerate SEO files
            </button>
        </div>

        <div id="seo-file-status" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($status['public_urls'] as $key => $url)
                @php $exists = $status['files_exist'][$key] ?? false; @endphp
                <a href="{{ $url }}" target="_blank" rel="noopener"
                   class="rounded-xl border px-3 py-3 text-sm transition
                   {{ $exists ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300' }}">
                    <span class="block font-semibold uppercase tracking-wide text-[11px]">{{ $key }}</span>
                    <span class="block mt-1 truncate text-xs opacity-80">{{ $exists ? 'Ready' : 'Missing' }}</span>
                </a>
            @endforeach
        </div>

        <div id="seo-url-counts" class="text-xs text-slate-500 dark:text-slate-400">
            @if(!empty($status['url_counts']))
                URL counts:
                @foreach($status['url_counts'] as $section => $count)
                    <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 mr-1 mb-1">{{ $section }}: {{ $count }}</span>
                @endforeach
            @endif
        </div>
    </div>
</x-page-card>

<x-page-card class="overflow-visible">
    <form id="seo-settings-form" class="category-builder" novalidate>
        @csrf
        <div class="category-builder__header px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h2 class="category-builder__title tracking-tight text-slate-900 dark:text-white">SEO generation settings</h2>
            <p class="category-builder__subtitle text-slate-500 dark:text-slate-400">
                Configure chunking, robots extras, security contact, humans.txt, and PWA manifest.
            </p>
        </div>

        <div class="px-4 py-5 sm:p-6 space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="chunk_size" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                        URLs per sitemap file <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="chunk_size" name="chunk_size" min="100" max="50000" required
                           value="{{ old('chunk_size', $s['chunk_size']) }}"
                           class="panel-input mt-1 block w-full" placeholder="750">
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Recommended 500–1000. Google allows up to 50,000 URLs / 50 MB per file.</p>
                    <p class="qcat-field-error" data-error-for="chunk_size" hidden></p>
                </div>
                <div>
                    <label for="security_contact_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Security contact email</label>
                    <input type="email" id="security_contact_email" name="security_contact_email"
                           value="{{ old('security_contact_email', $s['security_contact_email']) }}"
                           class="panel-input mt-1 block w-full" placeholder="security@examtube.in">
                    <p class="qcat-field-error" data-error-for="security_contact_email" hidden></p>
                </div>
            </div>

            <div>
                <label for="security_policy_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Security policy URL</label>
                <input type="url" id="security_policy_url" name="security_policy_url"
                       value="{{ old('security_policy_url', $s['security_policy_url']) }}"
                       class="panel-input mt-1 block w-full" placeholder="https://examtube.in/security-policy">
                <p class="qcat-field-error" data-error-for="security_policy_url" hidden></p>
            </div>

            <div>
                <label for="robots_extra" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Extra robots.txt rules</label>
                <textarea id="robots_extra" name="robots_extra" rows="4" class="panel-input mt-1 block w-full font-mono text-sm"
                          placeholder="Disallow: /internal/">{{ old('robots_extra', $s['robots_extra']) }}</textarea>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Appended after default Allow/Disallow and Sitemap lines.</p>
                <p class="qcat-field-error" data-error-for="robots_extra" hidden></p>
            </div>

            <div>
                <label for="humans_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">humans.txt content</label>
                <textarea id="humans_text" name="humans_text" rows="8" class="panel-input mt-1 block w-full font-mono text-sm"
                          placeholder="/* TEAM */">{{ old('humans_text', $s['humans_text']) }}</textarea>
                <p class="qcat-field-error" data-error-for="humans_text" hidden></p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Web app manifest</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="manifest_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" id="manifest_name" name="manifest_name" required maxlength="120"
                               value="{{ old('manifest_name', $s['manifest_name']) }}" class="panel-input mt-1 block w-full" placeholder="Examtube.in">
                        <p class="qcat-field-error" data-error-for="manifest_name" hidden></p>
                    </div>
                    <div>
                        <label for="manifest_short_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Short name <span class="text-red-500">*</span></label>
                        <input type="text" id="manifest_short_name" name="manifest_short_name" required maxlength="40"
                               value="{{ old('manifest_short_name', $s['manifest_short_name']) }}" class="panel-input mt-1 block w-full" placeholder="Examtube">
                        <p class="qcat-field-error" data-error-for="manifest_short_name" hidden></p>
                    </div>
                    <div>
                        <x-color-picker
                            name="manifest_theme_color"
                            id="manifest_theme_color"
                            label="Theme color"
                            :required="true"
                            :value="old('manifest_theme_color', $s['manifest_theme_color'])"
                            placeholder="#0f766e"
                        />
                        <p class="qcat-field-error" data-error-for="manifest_theme_color" hidden></p>
                    </div>
                    <div>
                        <x-color-picker
                            name="manifest_background_color"
                            id="manifest_background_color"
                            label="Background color"
                            :required="true"
                            :value="old('manifest_background_color', $s['manifest_background_color'])"
                            placeholder="#0b1220"
                        />
                        <p class="qcat-field-error" data-error-for="manifest_background_color" hidden></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3 rounded-b-2xl">
            <button type="submit" class="panel-button-primary" id="seo-save-btn">Save SEO settings</button>
        </div>
    </form>
</x-page-card>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/components/color-picker.css') }}?v={{ filemtime(public_path('css/components/color-picker.css')) }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.seoSettingsConfig = {
            updateUrl: @json(route('admin.settings.seo.update')),
            regenerateUrl: @json(route('admin.settings.seo.regenerate')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/components/color-picker.js') }}?v={{ filemtime(public_path('js/components/color-picker.js')) }}"></script>
    <script src="{{ asset('js/backend/settings-seo.js') }}?v={{ filemtime(public_path('js/backend/settings-seo.js')) }}"></script>
@endpush
