@extends('backend.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('content-container-class', 'max-w-none')

@php
    $profile = $user->profile;
    $socialLinks = $profile?->social_links ?? [];
    $nameParts = explode(' ', trim($user->name ?? 'User'));
    $initials = count($nameParts) >= 2
        ? strtoupper(substr($nameParts[0], 0, 1).substr($nameParts[1], 0, 1))
        : strtoupper(substr($user->name ?? 'User', 0, 2));
    $activeTab = session('status') === 'password-updated'
        ? 'security'
        : ($errors->hasAny(['address_line1', 'address_line2', 'city', 'state_region', 'postal_code', 'country'])
            ? 'address'
            : ($errors->hasAny(['social_links.*', 'social_links.website', 'social_links.linkedin', 'social_links.github', 'social_links.twitter', 'social_links.facebook'])
                ? 'social'
                : 'general'));
@endphp

@section('content')
<div
    id="profile-workspace"
    class="profile-page"
    x-data="{ activeTab: @js($activeTab) }"
    @profile-tab.window="activeTab = $event.detail"
>
    <section class="profile-hero">
        <div class="profile-hero__glow profile-hero__glow--one"></div>
        <div class="profile-hero__glow profile-hero__glow--two"></div>
        <div class="profile-hero__content">
            <div>
                <span class="profile-eyebrow">Account workspace</span>
                <h1>Make your profile feel like you.</h1>
                <p>Keep your identity, contact details, public links, and account security up to date.</p>
            </div>
            <div class="profile-hero__meta">
                <span class="profile-status-dot"></span>
                Active account
            </div>
        </div>
    </section>

    <div class="profile-layout">
        <aside class="profile-summary-card">
            <div class="profile-summary-card__cover"></div>
            <div class="profile-summary-card__body">
                <div class="profile-summary-avatar">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }} profile photo">
                    @else
                        <span>{{ $initials }}</span>
                    @endif
                </div>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
                @if(filled($user->username))
                    <p class="profile-summary-username">{{ '@'.$user->username }}</p>
                @endif

                <div class="profile-summary-card__facts">
                    <div>
                        <span>Account</span>
                        <strong>{{ ucfirst($profile?->status ?? 'active') }}</strong>
                    </div>
                    <div>
                        <span>Member since</span>
                        <strong>{{ $user->created_at?->format('M Y') ?? '—' }}</strong>
                    </div>
                    <div>
                        <span>Profile</span>
                        <strong>{{ $profile?->bio ? 'Personalized' : 'Getting started' }}</strong>
                    </div>
                    @if(filled($profile?->gender) || filled($profile?->date_of_birth))
                        <div>
                            <span>Personal</span>
                            <strong>
                                {{ $profile?->gender ? ucwords(str_replace('_', ' ', $profile->gender)) : '—' }}
                                @if($profile?->date_of_birth)
                                    · {{ $profile->date_of_birth->format('d M Y') }}
                                @endif
                            </strong>
                        </div>
                    @endif
                </div>

                <p class="profile-summary-card__hint">
                    A complete profile helps administrators and teammates identify you quickly.
                </p>
            </div>
        </aside>

        <section class="profile-main-card">
            <nav class="profile-tabs" aria-label="Profile sections">
                @foreach ([
                    'general' => ['General', 'Personal details'],
                    'address' => ['Address', 'Location details'],
                    'social' => ['Social', 'Public profiles'],
                    'security' => ['Security', 'Password & account'],
                ] as $tab => [$label, $description])
                    <button
                        type="button"
                        class="profile-tab"
                        :class="{ 'is-active': activeTab === '{{ $tab }}' }"
                        @click="activeTab = '{{ $tab }}'"
                        :aria-selected="activeTab === '{{ $tab }}'"
                        role="tab"
                    >
                        <span>{{ $label }}</span>
                        <small>{{ $description }}</small>
                    </button>
                @endforeach
            </nav>

            <form
                id="profile-form"
                method="post"
                action="{{ route('admin.profile.update') }}"
                class="profile-form"
                data-profile-form
                novalidate
            >
                @csrf
                @method('patch')
                <input type="hidden" name="cropped_avatar" id="cropped_avatar">
                <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">

                <div x-show="activeTab === 'general'" x-cloak role="tabpanel" class="profile-panel">
                    <header class="profile-panel__header">
                        <div>
                            <span class="profile-panel__kicker">Personal details</span>
                            <h2>General information</h2>
                            <p>Update the details used throughout your workspace.</p>
                        </div>
                    </header>

                    <div class="profile-avatar-editor">
                        <div class="profile-avatar-preview" data-avatar-preview>
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="Current profile avatar" data-avatar-image>
                            @else
                                <img src="" alt="Profile avatar preview" data-avatar-image hidden>
                            @endif
                            <span data-avatar-initials @if ($avatarUrl) hidden @endif>{{ $initials }}</span>
                        </div>
                        <div class="profile-avatar-editor__content">
                            <h3>Profile photo</h3>
                            <p>Choose a JPG, PNG, or WebP image. Crop it before saving for the best result.</p>
                            <div class="profile-avatar-actions">
                                <label for="avatar_input" class="profile-btn profile-btn--secondary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"/>
                                    </svg>
                                    {{ $avatarUrl ? 'Change photo' : 'Upload photo' }}
                                </label>
                                <input id="avatar_input" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                                <button type="button" class="profile-btn profile-btn--ghost" data-avatar-remove @if (! $avatarUrl) hidden @endif>
                                    Remove
                                </button>
                            </div>
                            <p class="profile-field-error" data-error-for="avatar_input">@error('cropped_avatar'){{ $message }}@enderror</p>
                        </div>
                    </div>

                    <div class="profile-field-grid">
                        <div class="profile-field" data-field-host>
                            <label for="name">Full name <span class="ca-req" aria-hidden="true">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                placeholder="e.g. Ananya Sharma"
                                autocomplete="name"
                                data-validate="name"
                                data-section="general"
                            >
                            <p class="profile-field-hint">Use the name your team will recognize.</p>
                            <p class="profile-field-error" data-error-for="name">@error('name'){{ $message }}@enderror</p>
                        </div>

                        <div class="profile-field" data-field-host>
                            <label for="username">Username</label>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="{{ old('username', $user->username) }}"
                                placeholder="e.g. ananya_sharma"
                                autocomplete="username"
                                data-section="general"
                            >
                            <p class="profile-field-hint">Letters, numbers, dashes, and underscores only.</p>
                            <p class="profile-field-error" data-error-for="username">@error('username'){{ $message }}@enderror</p>
                        </div>

                        <div class="profile-field" data-field-host>
                            <label for="email">Email address <span class="ca-req" aria-hidden="true">*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                placeholder="name@example.com"
                                autocomplete="email"
                                inputmode="email"
                                data-validate="email"
                                data-section="general"
                            >
                            <p class="profile-field-hint">Used for sign-in and account notifications.</p>
                            <p class="profile-field-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                        </div>

                        <div class="profile-field" data-field-host>
                            <label for="phone">Phone number</label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="{{ old('phone', $profile?->phone) }}"
                                placeholder="+91 98765 43210"
                                autocomplete="tel"
                                inputmode="tel"
                                data-validate="phone"
                                data-section="general"
                            >
                            <p class="profile-field-error" data-error-for="phone">@error('phone'){{ $message }}@enderror</p>
                        </div>

                        <div class="profile-field" data-field-host>
                            <label for="date_of_birth">Date of birth</label>
                            <input
                                type="text"
                                id="date_of_birth"
                                name="date_of_birth"
                                value="{{ old('date_of_birth', optional($profile?->date_of_birth)->format('Y-m-d')) }}"
                                placeholder="Select date of birth"
                                autocomplete="bday"
                                inputmode="numeric"
                                data-dob-picker
                                data-validate="dob"
                                data-section="general"
                                maxlength="10"
                            >
                            <p class="profile-field-hint">Future dates are not allowed.</p>
                            <p class="profile-field-error" data-error-for="date_of_birth">@error('date_of_birth'){{ $message }}@enderror</p>
                        </div>

                        <div class="profile-field" data-field-host>
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" data-section="general">
                                <option value="">Select gender</option>
                                @foreach ([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                    'other' => 'Other',
                                    'prefer_not_to_say' => 'Prefer not to say',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('gender', $profile?->gender) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="profile-field-error" data-error-for="gender">@error('gender'){{ $message }}@enderror</p>
                        </div>

                        <div class="profile-field profile-field--wide" data-field-host>
                            <div class="profile-label-row">
                                <label for="bio">Bio / About me</label>
                                <span data-bio-count>0 / 2000</span>
                            </div>
                            <textarea
                                id="bio"
                                name="bio"
                                rows="5"
                                maxlength="2000"
                                placeholder="Share your role, interests, or what you are currently learning…"
                                data-section="general"
                            >{{ old('bio', $profile?->bio) }}</textarea>
                            <p class="profile-field-error" data-error-for="bio">@error('bio'){{ $message }}@enderror</p>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'address'" x-cloak role="tabpanel" class="profile-panel">
                    <header class="profile-panel__header">
                        <div>
                            <span class="profile-panel__kicker">Location</span>
                            <h2>Address details</h2>
                            <p>Add an optional contact address for administrative use.</p>
                        </div>
                    </header>

                    <div class="profile-field-grid">
                        <div class="profile-field profile-field--wide" data-field-host>
                            <label for="address_line1">Address line 1</label>
                            <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1', $profile?->address_line1) }}" placeholder="House number and street name" autocomplete="address-line1" data-section="address">
                            <p class="profile-field-error" data-error-for="address_line1">@error('address_line1'){{ $message }}@enderror</p>
                        </div>
                        <div class="profile-field profile-field--wide" data-field-host>
                            <label for="address_line2">Address line 2</label>
                            <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2', $profile?->address_line2) }}" placeholder="Apartment, landmark, or area (optional)" autocomplete="address-line2" data-section="address">
                            <p class="profile-field-error" data-error-for="address_line2">@error('address_line2'){{ $message }}@enderror</p>
                        </div>
                        <div class="profile-field" data-field-host>
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $profile?->city) }}" placeholder="e.g. Bengaluru" autocomplete="address-level2" data-section="address">
                            <p class="profile-field-error" data-error-for="city">@error('city'){{ $message }}@enderror</p>
                        </div>
                        <div class="profile-field" data-field-host>
                            <label for="state_region">State / Region</label>
                            <input type="text" id="state_region" name="state_region" value="{{ old('state_region', $profile?->state_region) }}" placeholder="e.g. Karnataka" autocomplete="address-level1" data-section="address">
                            <p class="profile-field-error" data-error-for="state_region">@error('state_region'){{ $message }}@enderror</p>
                        </div>
                        <div class="profile-field" data-field-host>
                            <label for="postal_code">Postal / ZIP code</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" placeholder="e.g. 560001" autocomplete="postal-code" data-section="address">
                            <p class="profile-field-error" data-error-for="postal_code">@error('postal_code'){{ $message }}@enderror</p>
                        </div>
                        <div class="profile-field" data-field-host>
                            <label for="country">Country</label>
                            <select id="country" name="country" autocomplete="country" data-section="address">
                                <option value="">Choose your country</option>
                                @foreach (['IN' => 'India', 'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'AU' => 'Australia', 'AE' => 'United Arab Emirates', 'SG' => 'Singapore'] as $code => $country)
                                    <option value="{{ $code }}" @selected(old('country', $profile?->country) === $code)>{{ $country }}</option>
                                @endforeach
                            </select>
                            <p class="profile-field-error" data-error-for="country">@error('country'){{ $message }}@enderror</p>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'social'" x-cloak role="tabpanel" class="profile-panel">
                    <header class="profile-panel__header">
                        <div>
                            <span class="profile-panel__kicker">Online presence</span>
                            <h2>Social links</h2>
                            <p>Connect the professional profiles you want to share.</p>
                        </div>
                    </header>

                    <div class="profile-field-grid">
                        @foreach ([
                            'website' => ['Website', 'https://yourwebsite.com'],
                            'linkedin' => ['LinkedIn', 'https://linkedin.com/in/username'],
                            'github' => ['GitHub', 'https://github.com/username'],
                            'twitter' => ['X / Twitter', 'https://x.com/username'],
                            'facebook' => ['Facebook', 'https://facebook.com/username'],
                        ] as $network => [$label, $placeholder])
                            <div class="profile-field" data-field-host>
                                <label for="social_{{ $network }}">{{ $label }}</label>
                                <input
                                    type="url"
                                    id="social_{{ $network }}"
                                    name="social_links[{{ $network }}]"
                                    value="{{ old("social_links.{$network}", $socialLinks[$network] ?? '') }}"
                                    placeholder="{{ $placeholder }}"
                                    inputmode="url"
                                    data-validate="url"
                                    data-section="social"
                                >
                                <p class="profile-field-error" data-error-for="social_{{ $network }}">@error("social_links.{$network}"){{ $message }}@enderror</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div x-show="activeTab !== 'security'" x-cloak class="profile-form__footer">
                    <p>Your changes apply across the entire admin workspace.</p>
                    <button type="submit" class="profile-btn profile-btn--primary" data-profile-submit>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save profile
                    </button>
                </div>
            </form>

            <div x-show="activeTab === 'security'" x-cloak role="tabpanel" class="profile-panel">
                <header class="profile-panel__header">
                    <div>
                        <span class="profile-panel__kicker">Account protection</span>
                        <h2>Security settings</h2>
                        <p>Use a strong password and review destructive account actions carefully.</p>
                    </div>
                </header>
                <div class="profile-security-grid">
                    <div class="profile-security-card">
                        @include('profile.partials.update-password-form')
                    </div>
                    <div class="profile-security-card profile-security-card--danger">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div id="avatar-crop-modal" class="profile-crop-modal" hidden aria-hidden="true">
        <div class="profile-crop-modal__backdrop" data-crop-close></div>
        <div class="profile-crop-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="avatar-crop-title">
            <header>
                <div>
                    <span class="profile-panel__kicker">Photo editor</span>
                    <h2 id="avatar-crop-title">Crop your profile photo</h2>
                </div>
                <button type="button" class="profile-icon-btn" data-crop-close aria-label="Close photo editor">×</button>
            </header>
            <div class="profile-crop-stage">
                <img id="avatar-crop-image" src="" alt="Selected photo to crop">
            </div>
            <p class="profile-crop-help">Drag to reposition and use the mouse wheel or touch gesture to zoom.</p>
            <footer>
                <button type="button" class="profile-btn profile-btn--ghost" data-crop-close>Cancel</button>
                <button type="button" class="profile-btn profile-btn--primary" data-crop-apply>Use this photo</button>
            </footer>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/backend/profile.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/components/dob-datepicker.css') }}" data-dob-theme="1">
@endpush

@push('scripts')
    <script src="{{ versioned_asset('js/components/dob-datepicker.js') }}"></script>
    <script src="{{ versioned_asset('js/backend/profile.js') }}" defer></script>
@endpush
