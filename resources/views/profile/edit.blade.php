@extends('backend.layouts.app')

@section('page-title', 'My Profile')
@section('content-container-class', 'max-w-full')

@php
    $avatarUrl = $user->profile?->avatar
        ? \Illuminate\Support\Facades\Storage::url($user->profile->avatar)
        : null;

    $userName = $user->name ?? 'User';
    $nameParts = explode(' ', trim($userName));
    $initials = count($nameParts) >= 2
        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
        : strtoupper(substr($userName, 0, 2));

    $initialTab = session('status') === 'password-updated' ? 'security' : 'general';
@endphp

@section('content')
<div
    class="w-full mx-auto pb-8"
    x-data="profilePage({ initialTab: '{{ $initialTab }}', avatarUrl: @js($avatarUrl), initials: @js($initials) })"
    x-cloak
>
    {{-- Profile Hero --}}
    <div class="panel-card overflow-hidden mb-6">
        <div class="relative h-28 sm:h-32 bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIxIiBmaWxsPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDUpIi8+PC9zdmc+')] opacity-60"></div>
        </div>
        <div class="relative px-6 sm:px-8 pb-6">
            <div class="flex flex-col lg:flex-row lg:items-end gap-5 -mt-12">
                <div class="relative group shrink-0">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl border-4 border-white dark:border-slate-800 shadow-xl overflow-hidden bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <img
                            x-show="displayAvatarUrl && !removeAvatar"
                            :src="displayAvatarUrl"
                            alt="Profile avatar"
                            class="h-full w-full object-cover"
                        >
                        <span
                            x-show="showInitials"
                            x-text="initials"
                            class="text-2xl sm:text-3xl font-bold text-slate-400 dark:text-slate-300"
                        ></span>
                    </div>
                    <button
                        type="button"
                        @click="openAvatarPicker()"
                        class="absolute inset-0 rounded-2xl bg-black/0 group-hover:bg-black/45 flex items-center justify-center transition-all cursor-pointer"
                        aria-label="Change profile photo"
                    >
                        <svg class="h-7 w-7 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                    <input type="file" x-ref="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onAvatarSelected($event)">
                </div>

                <div class="flex-1 min-w-0 pb-1">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white truncate">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ $user->email }}</p>
                    <p x-show="hasPendingAvatar" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        New photo ready — save General Information to apply
                    </p>
                    <p x-show="removeAvatar" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
                        Photo marked for removal — save to confirm
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 pb-1">
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">
                        <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                        Active
                    </span>
                    <span class="text-xs text-slate-400 dark:text-slate-500">Member since {{ $user->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200 dark:border-slate-700 mb-6 font-medium text-sm text-slate-500 dark:text-slate-400">
        <ul class="flex overflow-x-auto gap-1 hide-scrollbar">
            <template x-for="tab in tabs" :key="tab.id">
                <li>
                    <button type="button" @click="activeTab = tab.id"
                            class="inline-flex items-center gap-2 px-4 pb-3 pt-1 border-b-2 transition-colors whitespace-nowrap focus:outline-none"
                            :class="activeTab === tab.id ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent hover:text-slate-800 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600'">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                        </svg>
                        <span x-text="tab.label"></span>
                    </button>
                </li>
            </template>
        </ul>
    </div>

    <div class="panel-card overflow-hidden">
        {{-- General --}}
        <div x-show="activeTab === 'general'" x-transition.opacity.duration.200ms style="display: none;">
            <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="divide-y divide-slate-200 dark:divide-slate-700/80">
                @csrf
                @method('patch')
                <input type="hidden" name="cropped_avatar" x-ref="croppedAvatarInput" value="">
                <input type="hidden" name="remove_avatar" :value="removeAvatar ? 1 : 0">

                <div class="px-6 sm:px-8 py-6 sm:py-8 space-y-8">
                    {{-- Avatar upload card --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 sm:p-6 dark:border-slate-700 dark:bg-slate-900/40">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                            <div class="shrink-0">
                                <div class="h-20 w-20 rounded-2xl overflow-hidden border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800 flex items-center justify-center">
                                    <img x-show="displayAvatarUrl && !removeAvatar" :src="displayAvatarUrl" alt="" class="h-full w-full object-cover">
                                    <span x-show="showInitials" x-text="initials" class="text-xl font-bold text-slate-400"></span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Profile Avatar</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Upload a photo, crop it to a square, then save your profile. JPG, PNG, GIF, or WebP up to 5MB.</p>
                                <p x-show="avatarError" x-text="avatarError" class="mt-2 text-sm text-rose-600 dark:text-rose-400"></p>
                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <button type="button" @click="openAvatarPicker()" class="panel-button-primary">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        Upload &amp; Crop
                                    </button>
                                    <button
                                        type="button"
                                        x-show="displayAvatarUrl && !removeAvatar"
                                        @click="markAvatarForRemoval()"
                                        class="panel-button-secondary text-rose-600 dark:text-rose-400"
                                    >
                                        Remove Photo
                                    </button>
                                    <button
                                        type="button"
                                        x-show="removeAvatar"
                                        @click="undoAvatarRemoval()"
                                        class="panel-button-secondary"
                                    >
                                        Undo Removal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Personal Information</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update your personal details. Changes will reflect across the platform.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="panel-input w-full" />
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-1">
                            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="panel-input w-full" />
                            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-1">
                            <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->profile?->phone) }}" class="panel-input w-full" placeholder="+91 9876543210" />
                            @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="bio" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Bio / About Me</label>
                        <textarea id="bio" name="bio" rows="5" class="panel-input w-full" placeholder="Tell us something about yourself...">{{ old('bio', $user->profile?->bio) }}</textarea>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Brief description for your profile. Maximum 2000 characters.</p>
                        @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('cropped_avatar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-4 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" class="text-sm text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Saved successfully.
                        </p>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="panel-button-primary sm:ml-auto">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- Address --}}
        <div x-show="activeTab === 'address'" x-transition.opacity.duration.200ms style="display: none;">
            <form method="post" action="{{ route('admin.profile.update') }}" class="divide-y divide-slate-200 dark:divide-slate-700/80">
                @csrf
                @method('patch')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <div class="px-6 sm:px-8 py-6 sm:py-8 space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Address Details</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage your address information for correspondence and records.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="address_line1" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1', $user->profile?->address_line1) }}" class="panel-input w-full" placeholder="Street address, P.O. box" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="address_line2" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Address Line 2 <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2', $user->profile?->address_line2) }}" class="panel-input w-full" placeholder="Apartment, suite, unit, building, floor" />
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">City</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $user->profile?->city) }}" class="panel-input w-full" />
                        </div>
                        <div>
                            <label for="state_region" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">State / Region</label>
                            <input type="text" id="state_region" name="state_region" value="{{ old('state_region', $user->profile?->state_region) }}" class="panel-input w-full" />
                        </div>
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Postal / Zip Code</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->profile?->postal_code) }}" class="panel-input w-full" />
                        </div>
                        <div>
                            <label for="country" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Country</label>
                            <select id="country" name="country" class="panel-input w-full">
                                <option value="">Select a country...</option>
                                <option value="IN" @selected(old('country', $user->profile?->country) === 'IN')>India</option>
                                <option value="US" @selected(old('country', $user->profile?->country) === 'US')>United States</option>
                                <option value="GB" @selected(old('country', $user->profile?->country) === 'GB')>United Kingdom</option>
                                <option value="CA" @selected(old('country', $user->profile?->country) === 'CA')>Canada</option>
                                <option value="AU" @selected(old('country', $user->profile?->country) === 'AU')>Australia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-4 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" class="text-sm text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Saved successfully.
                        </p>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="panel-button-primary sm:ml-auto">Update Address</button>
                </div>
            </form>
        </div>

        {{-- Social --}}
        <div x-show="activeTab === 'social'" x-transition.opacity.duration.200ms style="display: none;">
            <form method="post" action="{{ route('admin.profile.update') }}" class="divide-y divide-slate-200 dark:divide-slate-700/80">
                @csrf
                @method('patch')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <div class="px-6 sm:px-8 py-6 sm:py-8 space-y-6">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Social Profiles</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Link your social media accounts for better collaboration and visibility.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach ([
                            'linkedin' => ['label' => 'LinkedIn Profile', 'placeholder' => 'https://linkedin.com/in/username', 'icon' => 'M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z'],
                            'twitter' => ['label' => 'Twitter / X', 'placeholder' => 'https://twitter.com/username', 'icon' => 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z'],
                            'facebook' => ['label' => 'Facebook Profile', 'placeholder' => 'https://facebook.com/username', 'icon' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
                            'github' => ['label' => 'GitHub', 'placeholder' => 'https://github.com/username', 'icon' => 'M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z'],
                        ] as $key => $network)
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">{{ $network['label'] }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $network['icon'] }}"/></svg>
                                    </div>
                                    <input type="url" name="social_links[{{ $key }}]" value="{{ old("social_links.{$key}", $user->profile?->social_links[$key] ?? '') }}" class="panel-input w-full pl-10" placeholder="{{ $network['placeholder'] }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="px-6 sm:px-8 py-4 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" class="text-sm text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Saved successfully.
                        </p>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="panel-button-primary sm:ml-auto">Update Socials</button>
                </div>
            </form>
        </div>

        {{-- Security --}}
        <div x-show="activeTab === 'security'" x-transition.opacity.duration.200ms style="display: none;">
            <div class="px-6 sm:px-8 py-6 sm:py-8 space-y-8 max-w-3xl">
                @include('profile.partials.update-password-form')
                <hr class="border-slate-200 dark:border-slate-700">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

    {{-- Crop Modal --}}
    <div
        x-show="showCropModal"
        x-transition.opacity
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/65 backdrop-blur-sm"
        @keydown.escape.window="cancelCrop()"
        style="display: none;"
    >
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-800 rounded-2xl shadow-2xl overflow-hidden" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Crop your photo</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Drag to reposition. Resize the square selection as needed.</p>
                </div>
                <button @click="cancelCrop()" type="button" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 dark:hover:text-slate-300 transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 sm:p-6 bg-slate-100 dark:bg-slate-900/60">
                <div class="mx-auto w-full max-h-[min(420px,55vh)] overflow-hidden rounded-xl bg-slate-200 dark:bg-slate-950">
                    <img x-ref="cropImage" alt="Crop preview" class="block max-w-full">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <button @click="rotateCrop(-90)" type="button" class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Rotate left">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 014 4v7M3 10l4-4M3 10l4 4"/></svg>
                    </button>
                    <button @click="rotateCrop(90)" type="button" class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Rotate right">
                        <svg class="h-5 w-5 -scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 014 4v7M3 10l4-4M3 10l4 4"/></svg>
                    </button>
                    <button @click="resetCrop()" type="button" class="p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition" title="Reset">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
                <div class="flex items-center gap-3 sm:justify-end">
                    <button @click="cancelCrop()" type="button" class="panel-button-secondary">Cancel</button>
                    <button @click="applyCrop()" type="button" class="panel-button-primary">Apply Crop</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="{{ asset('js/backend/profile.js') }}?v={{ filemtime(public_path('js/backend/profile.js')) }}"></script>
@endpush
