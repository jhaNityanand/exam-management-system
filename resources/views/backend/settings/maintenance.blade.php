@extends('backend.layouts.app')

@section('title', 'Maintenance Mode')
@section('page-title', 'Maintenance Mode')
@section('content-container-class', 'max-w-5xl')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Maintenance Mode'],
    ]" />
@endsection

@section('content')
@php
    $s = $settings;
@endphp

<div id="maintenance-skeleton" class="space-y-4" hidden>
    <div class="panel-card p-6 animate-pulse space-y-4">
        <div class="h-6 w-48 rounded bg-slate-200 dark:bg-slate-700"></div>
        <div class="h-4 w-full rounded bg-slate-200 dark:bg-slate-700"></div>
        <div class="h-4 w-3/4 rounded bg-slate-200 dark:bg-slate-700"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <div class="h-10 rounded bg-slate-200 dark:bg-slate-700"></div>
            <div class="h-10 rounded bg-slate-200 dark:bg-slate-700"></div>
        </div>
    </div>
</div>

<div id="maintenance-form-wrap">
    <x-page-card class="overflow-visible">
        <form id="maintenance-form" class="category-builder" novalidate>
            @csrf

            <div class="category-builder__header px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="category-builder__title tracking-tight text-slate-900 dark:text-white">Maintenance Mode</h1>
                        <p class="category-builder__subtitle text-slate-500 dark:text-slate-400">
                            When enabled, frontend visitors see a branded maintenance page. Admin access stays available.
                        </p>
                    </div>
                    <div id="maintenance-status-pill"
                         class="inline-flex items-center gap-2 self-start rounded-full px-3 py-1.5 text-xs font-semibold
                         {{ $s['enabled'] ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' }}">
                        <span class="h-2 w-2 rounded-full {{ $s['enabled'] ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                        <span data-status-label>{{ $s['enabled'] ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-5 sm:p-6 space-y-8">
                {{-- Toggle --}}
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 p-4 sm:p-5">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="enabled" id="enabled" value="1"
                               class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               {{ !empty($s['enabled']) ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">Enable maintenance mode</span>
                            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                Public pages and candidate accounts will show the maintenance screen. Admins can still use the panel and log in.
                            </span>
                        </span>
                    </label>
                </div>

                {{-- Content --}}
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required maxlength="160"
                               value="{{ old('title', $s['title'] ?? '') }}"
                               class="panel-input mt-1 block w-full"
                               placeholder="We will be right back">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Shown as the main headline on the maintenance page.</p>
                        <p class="qcat-field-error" data-error-for="title" hidden></p>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Message <span class="text-red-500">*</span>
                        </label>
                        <textarea id="message" name="message" required rows="4" maxlength="2000"
                                  class="panel-input mt-1 block w-full"
                                  placeholder="We are currently performing scheduled maintenance…">{{ old('message', $s['message'] ?? '') }}</textarea>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Explain why the site is unavailable. Line breaks are preserved.</p>
                        <p class="qcat-field-error" data-error-for="message" hidden></p>
                    </div>

                    <div>
                        <label for="estimated_at" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Estimated availability
                        </label>
                        <input type="datetime-local" id="estimated_at" name="estimated_at"
                               value="{{ old('estimated_at', $s['estimated_at'] ?? '') }}"
                               class="panel-input mt-1 block w-full max-w-sm">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Optional. Displayed as “Expected back” when set.</p>
                        <p class="qcat-field-error" data-error-for="estimated_at" hidden></p>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Contact information</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contact email</label>
                            <input type="email" id="contact_email" name="contact_email" maxlength="190"
                                   value="{{ old('contact_email', $s['contact_email'] ?? '') }}"
                                   class="panel-input mt-1 block w-full"
                                   placeholder="hello@examtube.in">
                            <p class="qcat-field-error" data-error-for="contact_email" hidden></p>
                        </div>
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contact phone</label>
                            <input type="text" id="contact_phone" name="contact_phone" maxlength="40"
                                   value="{{ old('contact_phone', $s['contact_phone'] ?? '') }}"
                                   class="panel-input mt-1 block w-full"
                                   placeholder="+91 98765 43210">
                            <p class="qcat-field-error" data-error-for="contact_phone" hidden></p>
                        </div>
                    </div>
                </div>

                {{-- Social --}}
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Social media links</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        @foreach([
                            'social_facebook' => ['Facebook', 'https://facebook.com/examtube'],
                            'social_instagram' => ['Instagram', 'https://instagram.com/examtube'],
                            'social_linkedin' => ['LinkedIn', 'https://linkedin.com/company/examtube'],
                            'social_twitter' => ['Twitter / X', 'https://x.com/examtube'],
                            'social_youtube' => ['YouTube', 'https://youtube.com/@examtube'],
                            'social_telegram' => ['Telegram', 'https://t.me/examtube'],
                        ] as $field => [$label, $placeholder])
                            <div>
                                <label for="{{ $field }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>
                                <input type="url" id="{{ $field }}" name="{{ $field }}" maxlength="255"
                                       value="{{ old($field, $s[$field] ?? '') }}"
                                       class="panel-input mt-1 block w-full"
                                       placeholder="{{ $placeholder }}">
                                <p class="qcat-field-error" data-error-for="{{ $field }}" hidden></p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Media --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'logo_gallery_id',
                            'label' => 'Logo',
                            'multiple' => false,
                            'value' => $s['logo_gallery_id'] ?? null,
                            'previewUrl' => $logoPreview,
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Recommended: transparent PNG or SVG-style logo, about <strong>400×120</strong> px. Max ~2 MB.
                        </p>
                        <p class="qcat-field-error" data-error-for="logo_gallery_id" hidden></p>
                    </div>
                    <div>
                        @include('backend.partials.gallery-picker', [
                            'name' => 'background_gallery_id',
                            'label' => 'Background image',
                            'multiple' => false,
                            'value' => $s['background_gallery_id'] ?? null,
                            'previewUrl' => $backgroundPreview,
                            'kind' => 'image',
                        ])
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            Recommended: <strong>1920×1080</strong> px landscape photo. JPG/WebP, under 3 MB for faster load.
                        </p>
                        <p class="qcat-field-error" data-error-for="background_gallery_id" hidden></p>
                    </div>
                </div>
            </div>

            <div class="category-builder__footer px-4 py-4 sm:px-6 bg-slate-50 dark:bg-slate-900/50 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3 rounded-b-2xl">
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="panel-button-secondary text-center">
                    Preview public site
                </a>
                <button type="submit" class="panel-button-primary" id="maintenance-save-btn">
                    Save maintenance settings
                </button>
            </div>
        </form>
    </x-page-card>
</div>

@include('backend.partials.image-editor-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/backend/gallery-picker.css') }}?v={{ filemtime(public_path('css/backend/gallery-picker.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('css/backend/gallery.css') }}?v={{ filemtime(public_path('css/backend/gallery.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/backend/question-category-form.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ asset('js/backend/gallery-editor.js') }}?v={{ filemtime(public_path('js/backend/gallery-editor.js')) }}"></script>
    <script>
        window.galleryDataUrl = @json(route('admin.gallery.data'));
        window.galleryStoreUrl = @json(route('admin.gallery.store'));
        window.galleryCommitUrl = @json(route('admin.gallery.commit'));
        window.galleryCsrf = @json(csrf_token());
        window.contentFormConfig = {
            formId: 'maintenance-form',
            categorySelector: null,
            seoSlugId: null,
            baseUrl: null,
            tagItemClass: null,
            module: 'settings',
            resolveUrl: null,
            isCreate: false,
            existingMedia: {},
            skipFormSubmitHook: true,
        };
        window.maintenanceSettingsConfig = {
            updateUrl: @json(route('admin.settings.maintenance.update')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('js/backend/content-form-shared.js') }}?v={{ filemtime(public_path('js/backend/content-form-shared.js')) }}"></script>
    <script src="{{ asset('js/backend/settings-maintenance.js') }}?v={{ filemtime(public_path('js/backend/settings-maintenance.js')) }}"></script>
@endpush
