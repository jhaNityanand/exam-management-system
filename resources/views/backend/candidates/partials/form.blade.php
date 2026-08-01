@php
    /** @var \App\Models\User|null $candidate */
    $isEdit = filled($candidate?->id);
    $profile = $candidate?->profile;
    $social = $profile?->social_links ?? [];
    $countries = [
        'IN' => 'India',
        'US' => 'United States',
        'GB' => 'United Kingdom',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'AE' => 'United Arab Emirates',
        'SG' => 'Singapore',
    ];
    $avatarMeta = user_avatar($candidate, $candidate->name ?? 'Candidate');
    $initials = $avatarMeta['initials'];
    $avatarColor = $avatarMeta['color'];
@endphp

<div class="px-4 pt-5 pb-2 space-y-10 sm:px-6">
    {{-- Profile Photo --}}
    <section class="cand-section">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Profile Photo</h2>
            <p class="cand-section__desc">Upload a clear headshot. JPG, PNG, GIF, or WebP up to 2MB.</p>
        </div>
        <div class="cand-avatar">
            <div
                class="cand-avatar__preview"
                id="candidate-avatar-preview"
                @if (empty($avatarUrl)) style="background: {{ $avatarColor }}; color: #fff" @endif
            >
                @if (! empty($avatarUrl))
                    <img src="{{ $avatarUrl }}" alt="Profile photo">
                @else
                    <span id="candidate-avatar-initials">{{ $initials }}</span>
                @endif
            </div>
            <div class="cand-avatar__actions">
                <label class="panel-button-secondary cursor-pointer">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload photo
                    <input type="file" id="candidate-avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                </label>
                <button type="button" id="candidate-avatar-remove" class="panel-button-secondary">Remove</button>
                <p class="qcat-field-error" id="err-cropped_avatar"></p>
                @error('cropped_avatar')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <input type="hidden" name="cropped_avatar" id="cropped_avatar" value="{{ old('cropped_avatar') }}">
            <input type="hidden" name="remove_avatar" id="remove_avatar" value="{{ old('remove_avatar', '0') }}">
        </div>
    </section>

    {{-- Personal Information --}}
    <section class="cand-section">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Personal Information</h2>
            <p class="cand-section__desc">Core identity details used across exams and account screens.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $candidate->name ?? '') }}" maxlength="255" class="panel-input mt-1 block w-full" placeholder="e.g. Priya Sharma" autocomplete="name">
                <p class="qcat-field-error" id="err-name"></p>
                @error('name')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username', $candidate->username ?? '') }}" maxlength="64" class="panel-input mt-1 block w-full" placeholder="e.g. priya_sharma" autocomplete="username">
                <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Letters, numbers, dashes, and underscores only.</p>
                <p class="qcat-field-error" id="err-username"></p>
                @error('username')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="gender" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Gender</label>
                <select id="gender" name="gender" class="panel-input mt-1 block w-full" data-placeholder="Select gender">
                    <option value="">Select gender</option>
                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $profile?->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="qcat-field-error" id="err-gender"></p>
                @error('gender')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="date_of_birth" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date of Birth</label>
                <input
                    type="text"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth', optional($profile?->date_of_birth)->format('Y-m-d')) }}"
                    class="panel-input mt-1 block w-full"
                    placeholder="Select date of birth"
                    autocomplete="bday"
                    inputmode="numeric"
                    data-dob-picker
                    maxlength="10"
                >
                <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">Future dates are not allowed.</p>
                <p class="qcat-field-error" id="err-date_of_birth"></p>
                @error('date_of_birth')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="bio" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Short Bio</label>
                <textarea id="bio" name="bio" rows="3" maxlength="2000" class="panel-input mt-1 block w-full" placeholder="Brief introduction, goals, or exam focus areas">{{ old('bio', $profile?->bio) }}</textarea>
                <p class="qcat-field-error" id="err-bio"></p>
                @error('bio')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- Contact Details --}}
    <section class="cand-section">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Contact Details</h2>
            <p class="cand-section__desc">How the candidate can be reached for exam alerts and account recovery.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $candidate->email ?? '') }}" maxlength="255" class="panel-input mt-1 block w-full" placeholder="e.g. priya@example.com" autocomplete="email">
                <p class="qcat-field-error" id="err-email"></p>
                @error('email')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Mobile Number</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}" maxlength="30" class="panel-input mt-1 block w-full" placeholder="e.g. +91 98765 43210" autocomplete="tel">
                <p class="qcat-field-error" id="err-phone"></p>
                @error('phone')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- Address --}}
    <section class="cand-section">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Address</h2>
            <p class="cand-section__desc">Optional mailing / residential address.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="address_line1" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Address Line 1</label>
                <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1', $profile?->address_line1) }}" maxlength="255" class="panel-input mt-1 block w-full" placeholder="House / flat number, street name">
                <p class="qcat-field-error" id="err-address_line1"></p>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label for="address_line2" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Address Line 2</label>
                <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2', $profile?->address_line2) }}" maxlength="255" class="panel-input mt-1 block w-full" placeholder="Area, landmark (optional)">
                <p class="qcat-field-error" id="err-address_line2"></p>
            </div>
            <div>
                <label for="city" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">City</label>
                <input type="text" id="city" name="city" value="{{ old('city', $profile?->city) }}" maxlength="120" class="panel-input mt-1 block w-full" placeholder="e.g. Ahmedabad">
                <p class="qcat-field-error" id="err-city"></p>
            </div>
            <div>
                <label for="state_region" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">State / Region</label>
                <input type="text" id="state_region" name="state_region" value="{{ old('state_region', $profile?->state_region) }}" maxlength="120" class="panel-input mt-1 block w-full" placeholder="e.g. Gujarat">
                <p class="qcat-field-error" id="err-state_region"></p>
            </div>
            <div>
                <label for="postal_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" maxlength="32" class="panel-input mt-1 block w-full" placeholder="e.g. 380001">
                <p class="qcat-field-error" id="err-postal_code"></p>
            </div>
            <div>
                <label for="country" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Country</label>
                <select id="country" name="country" class="panel-input mt-1 block w-full" data-placeholder="Select country">
                    <option value="">Select country</option>
                    @foreach ($countries as $code => $label)
                        <option value="{{ $code }}" @selected(old('country', $profile?->country) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="qcat-field-error" id="err-country"></p>
            </div>
        </div>
    </section>

    {{-- Social Links --}}
    <section class="cand-section">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Social Links</h2>
            <p class="cand-section__desc">Optional public profiles linked to this candidate.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ([
                'website' => ['Website', 'https://yourwebsite.com'],
                'linkedin' => ['LinkedIn', 'https://linkedin.com/in/username'],
                'twitter' => ['Twitter / X', 'https://x.com/username'],
                'github' => ['GitHub', 'https://github.com/username'],
                'facebook' => ['Facebook', 'https://facebook.com/username'],
            ] as $key => [$label, $placeholder])
                <div>
                    <label for="social_{{ $key }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ $label }}</label>
                    <input
                        type="text"
                        id="social_{{ $key }}"
                        name="social_links[{{ $key }}]"
                        value="{{ old('social_links.'.$key, $social[$key] ?? '') }}"
                        maxlength="255"
                        class="panel-input mt-1 block w-full"
                        placeholder="{{ $placeholder }}"
                    >
                    <p class="qcat-field-error" id="err-social_links.{{ $key }}"></p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Verification --}}
    <section class="cand-section">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Verification</h2>
            <p class="cand-section__desc">Email verification status for this candidate account.</p>
        </div>
        <label class="cand-check">
            <input
                type="checkbox"
                name="email_verified"
                value="1"
                class="cand-check__input"
                @checked(old('email_verified', $isEdit ? filled($candidate?->email_verified_at) : false))
            >
            <span>
                <strong class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Mark email as verified</strong>
                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Skip the verification email and treat this address as confirmed.</span>
            </span>
        </label>
    </section>

    {{-- Account Settings --}}
    <section class="cand-section cand-section--last">
        <div class="cand-section__head">
            <h2 class="cand-section__title">Account Settings</h2>
            <p class="cand-section__desc">Login credentials and account availability.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status <span class="text-red-500">*</span></label>
                <select id="status" name="status" class="panel-input mt-1 block w-full">
                    <option value="active" @selected(old('status', $candidate->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $candidate->status ?? '') === 'inactive')>Inactive</option>
                </select>
                <p class="qcat-field-error" id="err-status"></p>
                @error('status')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Password
                    @unless ($isEdit)
                        <span class="text-red-500">*</span>
                    @endunless
                </label>
                <input type="password" id="password" name="password" class="panel-input mt-1 block w-full" autocomplete="new-password" placeholder="{{ $isEdit ? 'Leave blank to keep current' : 'Create a password' }}">
                <p class="qcat-field-error" id="err-password"></p>
                @error('password')<p class="qcat-field-error is-visible">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Confirm Password
                    @unless ($isEdit)
                        <span class="text-red-500">*</span>
                    @endunless
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="panel-input mt-1 block w-full" autocomplete="new-password" placeholder="Repeat password">
                <p class="qcat-field-error" id="err-password_confirmation"></p>
            </div>
        </div>
    </section>
</div>
