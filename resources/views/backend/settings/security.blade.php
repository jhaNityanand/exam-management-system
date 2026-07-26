@extends('backend.layouts.app')

@section('title', 'Security')
@section('page-title', 'Security')
@section('content-container-class', 'max-w-5xl')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Security'],
    ]" />
@endsection

@section('content')
@php $s = $settings; @endphp

<x-page-card class="overflow-visible">
    <form id="security-settings-form" class="category-builder" novalidate>
        @csrf
        <div class="category-builder__header px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <h1 class="category-builder__title text-slate-900 dark:text-white">Security</h1>
            <p class="category-builder__subtitle text-slate-500">reCAPTCHA protection and login lockout controls.</p>
        </div>

        <div class="px-4 py-5 sm:p-6 space-y-10">
            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Google reCAPTCHA</h2>
                <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 p-4">
                    <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                           {{ !empty($s['recaptcha_enabled']) ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Enable reCAPTCHA</span>
                        <span class="mt-1 block text-xs text-slate-500">Requires site key + secret from Google reCAPTCHA admin.</span>
                    </span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="recaptcha_version" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Version</label>
                        <select id="recaptcha_version" name="recaptcha_version" class="panel-input mt-1 block w-full">
                            <option value="v3" @selected(($s['recaptcha_version'] ?? 'v3') === 'v3')>v3 (invisible score)</option>
                            <option value="v2" @selected(($s['recaptcha_version'] ?? '') === 'v2')>v2 checkbox</option>
                        </select>
                    </div>
                    <div>
                        <label for="recaptcha_score_threshold" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">v3 score threshold</label>
                        <input type="number" step="0.1" min="0" max="1" id="recaptcha_score_threshold" name="recaptcha_score_threshold"
                               class="panel-input mt-1 block w-full" value="{{ old('recaptcha_score_threshold', $s['recaptcha_score_threshold'] ?? '0.5') }}">
                    </div>
                    <div>
                        <label for="recaptcha_site_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Site key</label>
                        <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" class="panel-input mt-1 block w-full font-mono text-sm"
                               value="{{ old('recaptcha_site_key', $s['recaptcha_site_key'] ?? '') }}">
                    </div>
                    <div>
                        <label for="recaptcha_secret_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Secret key</label>
                        <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key" autocomplete="new-password" class="panel-input mt-1 block w-full"
                               placeholder="{{ !empty($s['has_recaptcha_secret']) ? '••••••••  (leave blank to keep)' : 'Secret key' }}">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach([
                        'recaptcha_on_login' => 'Login',
                        'recaptcha_on_register' => 'Register',
                        'recaptcha_on_contact' => 'Contact',
                        'recaptcha_on_newsletter' => 'Newsletter',
                        'recaptcha_on_password_reset' => 'Password reset',
                    ] as $key => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm">
                            <input type="checkbox" id="{{ $key }}" name="{{ $key }}" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600"
                                   {{ !empty($s[$key]) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Login protection</h2>
                <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 p-4">
                    <input type="checkbox" id="login_lockout_enabled" name="login_lockout_enabled" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"
                           {{ !empty($s['login_lockout_enabled']) ? 'checked' : '' }}>
                    <span class="block text-sm font-semibold text-slate-900 dark:text-white">Enable login lockout / rate limiting</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="login_max_attempts" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Max attempts</label>
                        <input type="number" min="1" max="50" id="login_max_attempts" name="login_max_attempts" class="panel-input mt-1 block w-full"
                               value="{{ old('login_max_attempts', $s['login_max_attempts'] ?? 5) }}">
                        <p class="qcat-field-error" data-error-for="login_max_attempts" hidden></p>
                    </div>
                    <div>
                        <label for="login_decay_minutes" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lockout minutes</label>
                        <input type="number" min="1" max="1440" id="login_decay_minutes" name="login_decay_minutes" class="panel-input mt-1 block w-full"
                               value="{{ old('login_decay_minutes', $s['login_decay_minutes'] ?? 1) }}">
                        <p class="mt-1.5 text-xs text-slate-500">Minutes before the attempt window resets after a lockout.</p>
                        <p class="qcat-field-error" data-error-for="login_decay_minutes" hidden></p>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex justify-end border-t border-slate-100 dark:border-slate-800 px-4 py-4 sm:px-6">
            <button type="submit" id="security-save-btn" class="panel-button-primary">Save security settings</button>
        </div>
    </form>
</x-page-card>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.securitySettingsConfig = {
            updateUrl: @json(route('admin.settings.security.update')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ versioned_asset('js/backend/settings-security.js') }}"></script>
@endpush
