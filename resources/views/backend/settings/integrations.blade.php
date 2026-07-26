@extends('backend.layouts.app')

@section('title', 'Integrations & Privacy')
@section('page-title', 'Integrations & Privacy')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Integrations & Privacy'],
    ]" />
@endsection

@section('content')
@php $s = $settings; @endphp

<x-page-card class="overflow-visible">
    <form id="integrations-settings-form" class="category-builder" novalidate>
        @csrf
        <div class="category-builder__header px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h1 class="category-builder__title text-slate-900 dark:text-white">Integrations & Privacy</h1>
            <p class="category-builder__subtitle text-slate-500">Analytics tags, cookie consent, localization, and public feature toggles.</p>
        </div>

        <div class="px-4 py-5 sm:p-6 space-y-10">
            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Analytics & tags</h2>
                <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 p-4">
                    <input type="checkbox" id="analytics_enabled" name="analytics_enabled" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                           {{ !empty($s['analytics_enabled']) ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Enable analytics / marketing tags</span>
                        <span class="mt-1 block text-xs text-slate-500">When cookie consent is opt-in, tags load only after the visitor accepts.</span>
                    </span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label for="google_analytics_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">GA4 Measurement ID</label>
                        <input type="text" id="google_analytics_id" name="google_analytics_id" class="panel-input mt-1 block w-full font-mono text-sm"
                               value="{{ old('google_analytics_id', $s['google_analytics_id'] ?? '') }}" placeholder="G-XXXXXXXX">
                        <p class="qcat-field-error" data-error-for="google_analytics_id" hidden></p>
                    </div>
                    <div>
                        <label for="gtm_container_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">GTM Container ID</label>
                        <input type="text" id="gtm_container_id" name="gtm_container_id" class="panel-input mt-1 block w-full font-mono text-sm"
                               value="{{ old('gtm_container_id', $s['gtm_container_id'] ?? '') }}" placeholder="GTM-XXXXXXX">
                        <p class="qcat-field-error" data-error-for="gtm_container_id" hidden></p>
                    </div>
                    <div>
                        <label for="facebook_pixel_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Facebook Pixel ID</label>
                        <input type="text" id="facebook_pixel_id" name="facebook_pixel_id" class="panel-input mt-1 block w-full font-mono text-sm"
                               value="{{ old('facebook_pixel_id', $s['facebook_pixel_id'] ?? '') }}" placeholder="1234567890">
                        <p class="qcat-field-error" data-error-for="facebook_pixel_id" hidden></p>
                    </div>
                </div>
                <div>
                    <label for="custom_head_scripts" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Custom head scripts</label>
                    <textarea id="custom_head_scripts" name="custom_head_scripts" rows="4" class="panel-input mt-1 block w-full font-mono text-sm"
                              placeholder="<!-- optional third-party snippets -->">{{ old('custom_head_scripts', $s['custom_head_scripts'] ?? '') }}</textarea>
                    <p class="mt-1.5 text-xs text-slate-500">Rendered in &lt;head&gt; after consent (when required). Only paste trusted snippets.</p>
                </div>
                <div>
                    <label for="custom_body_scripts" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Custom body scripts</label>
                    <textarea id="custom_body_scripts" name="custom_body_scripts" rows="4" class="panel-input mt-1 block w-full font-mono text-sm">{{ old('custom_body_scripts', $s['custom_body_scripts'] ?? '') }}</textarea>
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Cookie consent</h2>
                <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 p-4">
                    <input type="checkbox" id="cookies_enabled" name="cookies_enabled" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                           {{ !empty($s['cookies_enabled']) ? 'checked' : '' }}>
                    <span class="block text-sm font-semibold text-slate-900 dark:text-white">Show cookie banner</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="cookies_mode" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mode</label>
                        <select id="cookies_mode" name="cookies_mode" class="panel-input mt-1 block w-full">
                            <option value="opt_in" @selected(($s['cookies_mode'] ?? '') === 'opt_in')>Opt-in (recommended / EU)</option>
                            <option value="opt_out" @selected(($s['cookies_mode'] ?? '') === 'opt_out')>Opt-out</option>
                            <option value="info_only" @selected(($s['cookies_mode'] ?? '') === 'info_only')>Info only</option>
                        </select>
                    </div>
                    <div>
                        <label for="cookies_policy_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Privacy policy URL</label>
                        <input type="text" id="cookies_policy_url" name="cookies_policy_url" class="panel-input mt-1 block w-full"
                               value="{{ old('cookies_policy_url', $s['cookies_policy_url'] ?? '') }}" placeholder="/privacy-policy">
                    </div>
                    <div>
                        <label for="cookies_title" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Banner title</label>
                        <input type="text" id="cookies_title" name="cookies_title" class="panel-input mt-1 block w-full" value="{{ old('cookies_title', $s['cookies_title'] ?? '') }}">
                        <p class="qcat-field-error" data-error-for="cookies_title" hidden></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cookies_accept_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Accept label</label>
                            <input type="text" id="cookies_accept_label" name="cookies_accept_label" class="panel-input mt-1 block w-full" value="{{ old('cookies_accept_label', $s['cookies_accept_label'] ?? '') }}">
                        </div>
                        <div>
                            <label for="cookies_reject_label" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Reject label</label>
                            <input type="text" id="cookies_reject_label" name="cookies_reject_label" class="panel-input mt-1 block w-full" value="{{ old('cookies_reject_label', $s['cookies_reject_label'] ?? '') }}">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="cookies_message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Banner message</label>
                        <textarea id="cookies_message" name="cookies_message" rows="3" class="panel-input mt-1 block w-full">{{ old('cookies_message', $s['cookies_message'] ?? '') }}</textarea>
                        <p class="qcat-field-error" data-error-for="cookies_message" hidden></p>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Localization & features</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="default_timezone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Default timezone</label>
                        <select id="default_timezone" name="default_timezone" class="panel-input mt-1 block w-full">
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" @selected(($s['default_timezone'] ?? '') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        <p class="qcat-field-error" data-error-for="default_timezone" hidden></p>
                    </div>
                    <div>
                        <label for="default_locale" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Default locale</label>
                        <input type="text" id="default_locale" name="default_locale" class="panel-input mt-1 block w-full" value="{{ old('default_locale', $s['default_locale'] ?? 'en') }}" placeholder="en">
                        <p class="qcat-field-error" data-error-for="default_locale" hidden></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <input type="checkbox" id="registration_enabled" name="registration_enabled" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                               {{ !empty($s['registration_enabled']) ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">Public registration</span>
                            <span class="mt-1 block text-xs text-slate-500">Allow new candidate sign-ups.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                        <input type="checkbox" id="newsletter_enabled" name="newsletter_enabled" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                               {{ !empty($s['newsletter_enabled']) ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">Newsletter signup</span>
                            <span class="mt-1 block text-xs text-slate-500">Show newsletter forms on the public site.</span>
                        </span>
                    </label>
                </div>
            </section>
        </div>

        <div class="flex justify-end border-t border-slate-100 dark:border-slate-800 px-4 py-4 sm:px-6">
            <button type="submit" id="integrations-save-btn" class="panel-button-primary">Save settings</button>
        </div>
    </form>
</x-page-card>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.integrationsSettingsConfig = {
            updateUrl: @json(route('admin.settings.integrations.update')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ versioned_asset('js/backend/settings-integrations.js') }}"></script>
@endpush
