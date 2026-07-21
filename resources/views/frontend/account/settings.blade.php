@extends('frontend.account.layout')

@php
    $seo = ['title' => 'Settings'];
@endphp

@section('account-eyebrow', 'Preferences')
@section('account-title', 'Settings')
@section('account-lead', 'Manage account details, security, notifications, and privacy.')

@section('account-content')
<div id="ca-settings"
     data-url="{{ $dataUrl }}"
     data-account-url="{{ route('frontend.account.settings.account') }}"
     data-password-url="{{ route('frontend.account.settings.password') }}"
     data-destroy-url="{{ route('frontend.account.settings.destroy') }}">
    <div id="ca-settings-alert" class="ca-alert" hidden></div>

    <nav class="ca-section-nav" aria-label="Settings sections">
        <button type="button" class="is-active" data-ca-tab="account">Account</button>
        <button type="button" data-ca-tab="security">Password &amp; Security</button>
        <button type="button" data-ca-tab="danger">Delete Account</button>
    </nav>

    <section id="ca-settings-skeleton" class="ca-card" aria-hidden="true">
        <div class="ca-skel ca-skel--line ca-skel--w40"></div>
        <div class="ca-skel ca-skel--line ca-skel--w70"></div>
        <div class="ca-skel ca-skel--block" style="margin-top:.75rem"></div>
    </section>

    <div id="ca-settings-content" hidden>
        <section class="ca-card" data-ca-panel="account">
            <div class="ca-card__head">
                <div>
                    <h2>Account settings</h2>
                    <p>Basic information, notifications, and privacy.</p>
                </div>
            </div>
            <form id="ca-account-form" class="ca-form" novalidate>
                <div class="ca-form__grid">
                    <div class="ca-field">
                        <label for="ca-set-name">Full Name <span class="ca-req">*</span></label>
                        <input id="ca-set-name" name="name" type="text" required>
                    </div>
                    <div class="ca-field">
                        <label for="ca-set-email">Email Address <span class="ca-req">*</span></label>
                        <input id="ca-set-email" name="email" type="email" required>
                    </div>
                </div>

                <h3 style="margin:0;font-size:.95rem">Notification preferences</h3>
                <div class="ca-list" id="ca-notifications"></div>

                <h3 style="margin:0;font-size:.95rem">Privacy settings</h3>
                <div class="ca-list" id="ca-privacy"></div>

                <div class="ca-actions">
                    <button type="submit" class="et-btn et-btn--primary" id="ca-account-save">Save settings</button>
                </div>
            </form>
        </section>

        <section class="ca-card" data-ca-panel="security" hidden>
            <div class="ca-card__head">
                <div>
                    <h2>Password &amp; security</h2>
                    <p>Update your password and review recent sessions.</p>
                </div>
            </div>

            <div class="ca-stats" id="ca-security-stats" style="margin-bottom:.85rem"></div>

            <form id="ca-password-form" class="ca-form" novalidate>
                <div class="ca-form__grid">
                    <div class="ca-field" style="grid-column:1/-1">
                        <label for="ca-current-password">Current Password <span class="ca-req">*</span></label>
                        <input id="ca-current-password" name="current_password" type="password" required autocomplete="current-password">
                    </div>
                    <div class="ca-field">
                        <label for="ca-password">New Password <span class="ca-req">*</span></label>
                        <input id="ca-password" name="password" type="password" required autocomplete="new-password">
                    </div>
                    <div class="ca-field">
                        <label for="ca-password-confirmation">Confirm Password <span class="ca-req">*</span></label>
                        <input id="ca-password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>
                <div class="ca-actions">
                    <button type="submit" class="et-btn et-btn--primary" id="ca-password-save">Update password</button>
                    <a href="{{ route('password.request') }}" class="et-btn et-btn--ghost" id="ca-password-reset">Reset password</a>
                </div>
            </form>

            <div style="margin-top:1rem">
                <h3 style="margin:0 0 .55rem;font-size:.95rem">Recent login sessions</h3>
                <div class="ca-list" id="ca-sessions"></div>
            </div>
        </section>

        <section class="ca-card ca-danger" data-ca-panel="danger" hidden>
            <div class="ca-card__head">
                <div>
                    <h2>Delete account</h2>
                    <p>Permanently remove your account and associated candidate data.</p>
                </div>
            </div>
            <p class="ca-help" style="color:inherit">This action cannot be undone. You will need to confirm with your password and type DELETE.</p>
            <div class="ca-actions">
                <button type="button" class="et-btn et-btn--primary" id="ca-delete-open" style="background:#dc2626;border-color:#dc2626">Delete my account</button>
            </div>
        </section>
    </div>
</div>

<div id="ca-delete-modal" class="ca-modal" hidden>
    <div class="ca-modal__backdrop" data-ca-modal-close></div>
    <div class="ca-modal__panel" role="dialog" aria-modal="true" aria-labelledby="ca-delete-title">
        <h2 id="ca-delete-title" style="margin:0;font-size:1.1rem">Confirm account deletion</h2>
        <p class="ca-help">Enter your password and type <strong>DELETE</strong> to confirm.</p>
        <form id="ca-delete-form" class="ca-form">
            <div class="ca-field">
                <label for="ca-delete-password">Password <span class="ca-req">*</span></label>
                <input id="ca-delete-password" name="password" type="password" required autocomplete="current-password">
            </div>
            <div class="ca-field">
                <label for="ca-delete-confirm">Type DELETE <span class="ca-req">*</span></label>
                <input id="ca-delete-confirm" name="confirmation" type="text" required placeholder="DELETE">
            </div>
            <div class="ca-actions">
                <button type="button" class="et-btn et-btn--ghost" data-ca-modal-close>Cancel</button>
                <button type="submit" class="et-btn et-btn--primary" id="ca-delete-submit" style="background:#dc2626;border-color:#dc2626">Delete account</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('account-scripts')
<script src="{{ versioned_asset('js/frontend/account-settings.js') }}"></script>
@endpush
