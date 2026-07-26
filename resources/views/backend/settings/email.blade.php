@extends('backend.layouts.app')

@section('title', 'Email Configuration')
@section('page-title', 'Email Configuration')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Email Configuration'],
    ]" />
@endsection

@section('content')
@php $s = $settings; @endphp

<x-page-card class="overflow-visible">
    <form id="email-settings-form" class="category-builder" novalidate>
        @csrf

        <div class="category-builder__header px-4 py-6 sm:px-6 border-b border-slate-100 dark:border-slate-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="category-builder__title tracking-tight text-slate-900 dark:text-white">Email Configuration</h1>
                    <p class="category-builder__subtitle text-slate-500 dark:text-slate-400">
                        Configure SMTP delivery for password resets, notifications, and system mail. Google OAuth credentials can be stored here for a future sign-in rollout.
                    </p>
                </div>
                <div id="email-mailer-pill"
                     class="inline-flex items-center gap-2 self-start rounded-full px-3 py-1.5 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    <span data-mailer-label>{{ $s['mailer_label'] ?? 'Log (development)' }}</span>
                </div>
            </div>
        </div>

        <div class="px-4 py-5 sm:p-6 space-y-10" x-data="{ mailer: @js(old('mailer', $s['mailer'] ?? 'log')) }">
            {{-- Transport --}}
            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Mail transport</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Use Log during local development. Switch to SMTP for production delivery.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="mailer" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Transport <span class="text-red-500">*</span></label>
                        <select id="mailer" name="mailer" class="panel-input mt-1 block w-full" x-model="mailer" required>
                            <option value="log">Log (development)</option>
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                        </select>
                        <p class="qcat-field-error" data-error-for="mailer" hidden></p>
                    </div>

                    <div>
                        <label for="encryption" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Encryption</label>
                        <select id="encryption" name="encryption" class="panel-input mt-1 block w-full">
                            @foreach(['tls' => 'TLS (recommended)', 'ssl' => 'SSL', 'none' => 'None'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('encryption', $s['encryption'] ?? 'tls') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="qcat-field-error" data-error-for="encryption" hidden></p>
                    </div>
                </div>

                <div x-show="mailer === 'smtp'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-6 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 p-4 sm:p-5">
                    <div class="sm:col-span-2">
                        <label for="host" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SMTP host <span class="text-red-500">*</span></label>
                        <input type="text" id="host" name="host" maxlength="255" class="panel-input mt-1 block w-full"
                               value="{{ old('host', $s['host'] ?? '') }}" placeholder="smtp.mailgun.org">
                        <p class="qcat-field-error" data-error-for="host" hidden></p>
                    </div>
                    <div>
                        <label for="port" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Port <span class="text-red-500">*</span></label>
                        <input type="number" id="port" name="port" min="1" max="65535" class="panel-input mt-1 block w-full"
                               value="{{ old('port', $s['port'] ?? 587) }}">
                        <p class="mt-1.5 text-xs text-slate-500">Common: 587 (TLS), 465 (SSL), 25.</p>
                        <p class="qcat-field-error" data-error-for="port" hidden></p>
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username</label>
                        <input type="text" id="username" name="username" maxlength="255" autocomplete="off" class="panel-input mt-1 block w-full"
                               value="{{ old('username', $s['username'] ?? '') }}">
                        <p class="qcat-field-error" data-error-for="username" hidden></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                        <input type="password" id="password" name="password" maxlength="500" autocomplete="new-password" class="panel-input mt-1 block w-full"
                               placeholder="{{ !empty($s['has_smtp_password']) ? '••••••••  (leave blank to keep current)' : 'SMTP password' }}">
                        <p class="mt-1.5 text-xs text-slate-500">Stored encrypted. Leave blank to keep the existing password.</p>
                        <p class="qcat-field-error" data-error-for="password" hidden></p>
                    </div>
                </div>
            </section>

            {{-- From --}}
            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Sender identity</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Used as the global From header for outbound mail.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="from_address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">From address <span class="text-red-500">*</span></label>
                        <input type="email" id="from_address" name="from_address" required maxlength="190" class="panel-input mt-1 block w-full"
                               value="{{ old('from_address', $s['from_address'] ?? '') }}" placeholder="noreply@examtube.in">
                        <p class="qcat-field-error" data-error-for="from_address" hidden></p>
                    </div>
                    <div>
                        <label for="from_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">From name <span class="text-red-500">*</span></label>
                        <input type="text" id="from_name" name="from_name" required maxlength="120" class="panel-input mt-1 block w-full"
                               value="{{ old('from_name', $s['from_name'] ?? '') }}" placeholder="Examtube.in">
                        <p class="qcat-field-error" data-error-for="from_name" hidden></p>
                    </div>
                </div>
            </section>

            {{-- Test email --}}
            <section class="space-y-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-600 p-4 sm:p-5">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Send test email</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Saves nothing — uses the currently saved transport settings.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="flex-1">
                        <label for="test_to" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Recipient</label>
                        <input type="email" id="test_to" name="test_to" class="panel-input mt-1 block w-full"
                               value="{{ auth()->user()->email ?? '' }}" placeholder="you@example.com">
                        <p class="qcat-field-error" data-error-for="test_to" hidden></p>
                    </div>
                    <button type="button" id="email-test-btn" class="panel-button-secondary text-sm whitespace-nowrap">
                        Send test email
                    </button>
                </div>
            </section>

            {{-- Google OAuth (UI only) --}}
            <section class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Google OAuth</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Store Google Cloud OAuth credentials here. Sign-in button wiring is not active yet — this panel is ready for a future release.
                        </p>
                    </div>
                    <span class="inline-flex self-start rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                        UI only
                    </span>
                </div>

                <label class="flex items-start gap-3 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 p-4">
                    <input type="checkbox" name="google_oauth_enabled" id="google_oauth_enabled" value="1"
                           class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           {{ !empty($s['google_oauth_enabled']) ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Mark Google sign-in as ready</span>
                        <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                            Credentials are saved encrypted. Public “Continue with Google” will use them once auth wiring ships.
                        </span>
                    </span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <label for="google_client_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client ID</label>
                        <input type="text" id="google_client_id" name="google_client_id" maxlength="255" class="panel-input mt-1 block w-full font-mono text-sm"
                               value="{{ old('google_client_id', $s['google_client_id'] ?? '') }}" placeholder="xxxxx.apps.googleusercontent.com">
                        <p class="qcat-field-error" data-error-for="google_client_id" hidden></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="google_client_secret" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client secret</label>
                        <input type="password" id="google_client_secret" name="google_client_secret" maxlength="500" autocomplete="new-password" class="panel-input mt-1 block w-full"
                               placeholder="{{ !empty($s['has_google_client_secret']) ? '••••••••  (leave blank to keep current)' : 'Google client secret' }}">
                        <p class="qcat-field-error" data-error-for="google_client_secret" hidden></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="google_redirect_uri" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Authorized redirect URI</label>
                        <input type="url" id="google_redirect_uri" name="google_redirect_uri" maxlength="500" class="panel-input mt-1 block w-full font-mono text-sm"
                               value="{{ old('google_redirect_uri', $s['google_redirect_uri'] ?? '') }}">
                        <p class="mt-1.5 text-xs text-slate-500">Add this exact URI in your Google Cloud Console OAuth client.</p>
                        <p class="qcat-field-error" data-error-for="google_redirect_uri" hidden></p>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800 px-4 py-4 sm:px-6">
            <button type="submit" id="email-save-btn" class="panel-button-primary">Save email settings</button>
        </div>
    </form>
</x-page-card>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.emailSettingsConfig = {
            updateUrl: @json(route('admin.settings.email.update')),
            testUrl: @json(route('admin.settings.email.test')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ versioned_asset('js/backend/settings-email.js') }}"></script>
@endpush
