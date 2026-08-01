@extends('backend.layouts.app')

@section('title', 'Maintenance Mode')
@section('page-title', 'Maintenance Mode')
@section('content-container-class', 'max-w-none')

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

<div id="maintenance-form-wrap" class="maint-page">
    <x-page-card class="overflow-visible">
        <form id="maintenance-form" class="category-builder" novalidate>
            @csrf

            <div class="maint-header">
                <div class="maint-header__copy">
                    <h1 class="maint-header__title">Maintenance Mode</h1>
                    <p class="maint-header__subtitle">
                        When enabled, only the public frontend shows the maintenance page. The admin panel and staff login remain available.
                    </p>
                </div>
                <div id="maintenance-status-pill"
                     class="maint-status {{ $s['enabled'] ? 'maint-status--on' : 'maint-status--off' }}">
                    <span class="maint-status__dot" aria-hidden="true"></span>
                    <span data-status-label>{{ $s['enabled'] ? 'Enabled' : 'Disabled' }}</span>
                </div>
            </div>

            <div class="maint-body">
                {{-- Toggle --}}
                <section class="maint-section maint-section--toggle">
                    <label class="maint-toggle" for="enabled">
                        <input type="checkbox" name="enabled" id="enabled" value="1"
                               class="maint-toggle__input"
                               {{ !empty($s['enabled']) ? 'checked' : '' }}>
                        <span class="maint-toggle__copy">
                            <span class="maint-toggle__title">Enable maintenance mode</span>
                            <span class="maint-toggle__hint">
                                Visitors and candidates see the branded maintenance screen. Admins keep working in the panel.
                            </span>
                        </span>
                    </label>
                </section>

                {{-- Page content --}}
                <section class="maint-section">
                    <div class="maint-section__head">
                        <h2 class="maint-section__title">Page content</h2>
                        <p class="maint-section__hint">Headline, rich message, and restore time shown on the public maintenance page.</p>
                    </div>

                    <div class="maint-field">
                        <label for="title" class="maint-label">Title <span class="maint-req">*</span></label>
                        <input type="text" id="title" name="title" required maxlength="160"
                               value="{{ old('title', $s['title'] ?? '') }}"
                               class="panel-input maint-input"
                               placeholder="We will be right back">
                        <p class="maint-help">Main headline on the maintenance page.</p>
                        <p class="qcat-field-error" data-error-for="title" hidden></p>
                    </div>

                    <div class="maint-field">
                        <x-rich-text-editor
                            label="Message"
                            input-id="message"
                            name="message"
                            :value="old('message', $s['message'] ?? '')"
                            placeholder="Explain what is happening and when visitors should expect the site back…"
                            :height="280"
                            :required="true"
                            preset="header"
                            module="settings"
                            help="Supports formatting, links, and lists. Shown as rich text on the public page."
                        />
                        <p class="qcat-field-error" data-error-for="message" hidden></p>
                    </div>

                    <div class="maint-field">
                        <x-date-time-picker
                            name="estimated_at"
                            id="estimated_at"
                            mode="datetime"
                            label="Restore date & time"
                            :value="old('estimated_at', $s['estimated_at'] ?? '')"
                            help="Powers the countdown timer and “Expected back” timestamp on the public page."
                        />
                        <p class="qcat-field-error" data-error-for="estimated_at" hidden></p>
                    </div>
                </section>

                {{-- Branding media --}}
                <section class="maint-section">
                    <div class="maint-section__head">
                        <h2 class="maint-section__title">Branding</h2>
                        <p class="maint-section__hint">Logo and optional background for the public maintenance page.</p>
                    </div>
                    <div class="maint-media-grid">
                        <div class="maint-field">
                            @include('backend.partials.gallery-picker', [
                                'name' => 'logo_gallery_id',
                                'label' => 'Logo',
                                'multiple' => false,
                                'value' => $s['logo_gallery_id'] ?? null,
                                'previewUrl' => $logoPreview,
                                'kind' => 'image',
                            ])
                            <p class="maint-help">Transparent PNG preferred, about <strong>400×120</strong> px.</p>
                            <p class="qcat-field-error" data-error-for="logo_gallery_id" hidden></p>
                        </div>
                        <div class="maint-field">
                            @include('backend.partials.gallery-picker', [
                                'name' => 'background_gallery_id',
                                'label' => 'Background image',
                                'multiple' => false,
                                'value' => $s['background_gallery_id'] ?? null,
                                'previewUrl' => $backgroundPreview,
                                'kind' => 'image',
                            ])
                            <p class="maint-help">Landscape photo, about <strong>1920×1080</strong> px.</p>
                            <p class="qcat-field-error" data-error-for="background_gallery_id" hidden></p>
                        </div>
                    </div>
                </section>

                {{-- Social --}}
                <section class="maint-section maint-section--last">
                    <div class="maint-section__head">
                        <h2 class="maint-section__title">Social media</h2>
                        <p class="maint-section__hint">Optional links shown at the bottom of the maintenance page. Leave blank to hide a platform.</p>
                    </div>
                    <div class="maint-social-grid">
                        @foreach([
                            'social_facebook' => ['Facebook', 'https://facebook.com/examtube', 'facebook'],
                            'social_instagram' => ['Instagram', 'https://instagram.com/examtube', 'instagram'],
                            'social_linkedin' => ['LinkedIn', 'https://linkedin.com/company/examtube', 'linkedin'],
                            'social_twitter' => ['Twitter / X', 'https://x.com/examtube', 'twitter'],
                            'social_youtube' => ['YouTube', 'https://youtube.com/@examtube', 'youtube'],
                            'social_telegram' => ['Telegram', 'https://t.me/examtube', 'telegram'],
                        ] as $field => [$label, $placeholder, $platform])
                            <div class="maint-social-field">
                                <label for="{{ $field }}" class="maint-label maint-label--social">
                                    <span class="maint-social-field__icon" aria-hidden="true">
                                        @include('backend.partials.social-platform-icon', ['platform' => $platform, 'size' => 14])
                                    </span>
                                    {{ $label }}
                                </label>
                                <input type="url" id="{{ $field }}" name="{{ $field }}" maxlength="255"
                                       value="{{ old($field, $s[$field] ?? '') }}"
                                       class="panel-input maint-input"
                                       placeholder="{{ $placeholder }}"
                                       inputmode="url"
                                       autocomplete="url">
                                <p class="qcat-field-error" data-error-for="{{ $field }}" hidden></p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="maint-footer">
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
    <link rel="stylesheet" href="{{ asset('css/components/datetime-picker.css') }}?v={{ filemtime(public_path('css/components/datetime-picker.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/components/rich-text-editor.css') }}?v={{ filemtime(public_path('css/components/rich-text-editor.css')) }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/maintenance.css') }}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="{{ versioned_asset('js/backend/gallery-editor.js') }}"></script>
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
    <script src="{{ versioned_asset('js/components/datetime-picker.js') }}"></script>
    <script src="{{ versioned_asset('js/components/editor.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/content-form-shared.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/settings-maintenance.js') }}"></script>
@endpush
