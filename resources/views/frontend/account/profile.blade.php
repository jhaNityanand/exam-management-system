@extends('frontend.account.layout')

@php
    $seo = ['title' => 'Profile'];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('css/components/dob-datepicker.css') }}" data-dob-theme="1">
@endpush

@section('account-eyebrow', 'Identity')
@section('account-title', 'Profile')
@section('account-lead', 'Update your photo, personal details, address, and social links.')

@section('account-content')
<div id="ca-profile"
     data-url="{{ $dataUrl }}"
     data-save-url="{{ route('frontend.account.profile.update') }}">
    <div id="ca-profile-alert" class="ca-alert" hidden></div>

    <section id="ca-profile-skeleton" class="ca-card" aria-hidden="true">
        <div class="ca-skel ca-skel--line ca-skel--w40"></div>
        <div class="ca-skel ca-skel--line ca-skel--w70"></div>
        <div class="ca-skel ca-skel--block" style="margin-top:.75rem"></div>
    </section>

    <div id="ca-profile-content" hidden>
        <section class="ca-card">
            <div class="ca-card__head">
                <div>
                    <h2>Profile completion</h2>
                    <p id="ca-completion-text">Loading…</p>
                </div>
            </div>
            <div class="ca-progress">
                <div class="ca-progress__meta"><span>Progress</span><span id="ca-completion-pct">0%</span></div>
                <div class="ca-progress__track"><div class="ca-progress__fill" id="ca-completion-fill" style="width:0%"></div></div>
            </div>
            <div class="ca-stats" style="margin-top:.85rem" id="ca-profile-stats"></div>
        </section>

        <section class="ca-card">
            <form id="ca-profile-form" class="ca-form" novalidate>
                <div class="ca-avatar">
                    <div class="ca-avatar__preview" id="ca-avatar-preview">
                        <span id="ca-avatar-initials">{{ strtoupper(mb_substr($user->name ?? 'U', 0, 1)) }}</span>
                    </div>
                    <div>
                        <strong>Profile picture</strong>
                        <p class="ca-help">JPG, PNG, or WebP up to 2MB.</p>
                        <div class="ca-actions" style="margin-top:.45rem">
                            <label class="et-btn et-btn--ghost et-btn--sm" style="cursor:pointer">
                                Upload photo
                                <input type="file" id="ca-avatar-input" accept="image/*" hidden>
                            </label>
                            <button type="button" class="et-btn et-btn--ghost et-btn--sm" id="ca-avatar-remove">Remove</button>
                        </div>
                        <input type="hidden" name="cropped_avatar" id="ca-cropped-avatar" value="">
                        <input type="hidden" name="remove_avatar" id="ca-remove-avatar" value="0">
                    </div>
                </div>

                <div class="ca-form__grid">
                    <div class="ca-field">
                        <label for="ca-name">Full Name <span class="ca-req">*</span></label>
                        <input id="ca-name" name="name" type="text" required maxlength="255" placeholder="e.g. Priya Sharma">
                    </div>
                    <div class="ca-field">
                        <label for="ca-username">Username</label>
                        <input id="ca-username" name="username" type="text" maxlength="64" autocomplete="username" placeholder="e.g. priya_sharma">
                    </div>
                    <div class="ca-field">
                        <label for="ca-email">Email Address <span class="ca-req">*</span></label>
                        <input id="ca-email" name="email" type="email" required maxlength="255" placeholder="e.g. priya@example.com">
                    </div>
                    <div class="ca-field">
                        <label for="ca-phone">Phone Number</label>
                        <input id="ca-phone" name="phone" type="text" maxlength="30" placeholder="e.g. +91 98765 43210">
                    </div>
                    <div class="ca-field">
                        <label for="ca-dob">Date of Birth</label>
                        <input
                            id="ca-dob"
                            name="date_of_birth"
                            type="text"
                            placeholder="Select date of birth"
                            autocomplete="bday"
                            inputmode="numeric"
                            data-dob-picker
                            maxlength="10"
                        >
                        <p class="ca-help">Future dates are not allowed.</p>
                    </div>
                    <div class="ca-field">
                        <label for="ca-gender">Gender</label>
                        <select id="ca-gender" name="gender">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                            <option value="prefer_not_to_say">Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <div class="ca-field">
                    <label for="ca-bio">Short Bio</label>
                    <textarea id="ca-bio" name="bio" maxlength="2000" placeholder="Write a short introduction about yourself, your goals, or exam focus areas"></textarea>
                </div>

                <h2 style="margin:0;font-size:1rem">Address</h2>
                <div class="ca-form__grid">
                    <div class="ca-field" style="grid-column:1/-1">
                        <label for="ca-address1">Address line 1</label>
                        <input id="ca-address1" name="address_line1" type="text" maxlength="255" placeholder="House / flat number, street name">
                    </div>
                    <div class="ca-field" style="grid-column:1/-1">
                        <label for="ca-address2">Address line 2</label>
                        <input id="ca-address2" name="address_line2" type="text" maxlength="255" placeholder="Area, landmark (optional)">
                    </div>
                    <div class="ca-field">
                        <label for="ca-city">City</label>
                        <input id="ca-city" name="city" type="text" maxlength="120" placeholder="e.g. Ahmedabad">
                    </div>
                    <div class="ca-field">
                        <label for="ca-state">State / Region</label>
                        <input id="ca-state" name="state_region" type="text" maxlength="120" placeholder="e.g. Gujarat">
                    </div>
                    <div class="ca-field">
                        <label for="ca-postal">Postal code</label>
                        <input id="ca-postal" name="postal_code" type="text" maxlength="32" placeholder="e.g. 380001">
                    </div>
                    <div class="ca-field">
                        <label for="ca-country">Country (ISO)</label>
                        <input id="ca-country" name="country" type="text" maxlength="2" placeholder="e.g. IN">
                    </div>
                </div>

                <h2 style="margin:0;font-size:1rem">Social links</h2>
                <div class="ca-form__grid">
                    <div class="ca-field">
                        <label for="ca-website">Website</label>
                        <input id="ca-website" name="social_links[website]" type="text" maxlength="255" placeholder="https://yourwebsite.com">
                    </div>
                    <div class="ca-field">
                        <label for="ca-linkedin">LinkedIn</label>
                        <input id="ca-linkedin" name="social_links[linkedin]" type="text" maxlength="255" placeholder="https://linkedin.com/in/username">
                    </div>
                    <div class="ca-field">
                        <label for="ca-twitter">Twitter / X</label>
                        <input id="ca-twitter" name="social_links[twitter]" type="text" maxlength="255" placeholder="https://x.com/username">
                    </div>
                    <div class="ca-field">
                        <label for="ca-github">GitHub</label>
                        <input id="ca-github" name="social_links[github]" type="text" maxlength="255" placeholder="https://github.com/username">
                    </div>
                    <div class="ca-field">
                        <label for="ca-facebook">Facebook</label>
                        <input id="ca-facebook" name="social_links[facebook]" type="text" maxlength="255" placeholder="https://facebook.com/username">
                    </div>
                </div>

                <div class="ca-actions">
                    <button type="submit" class="et-btn et-btn--primary" id="ca-profile-save">Save profile</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@push('account-scripts')
<script src="{{ versioned_asset('js/components/dob-datepicker.js') }}"></script>
<script src="{{ versioned_asset('js/frontend/account-profile.js') }}"></script>
@endpush
