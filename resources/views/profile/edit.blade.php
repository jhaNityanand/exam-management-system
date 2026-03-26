@extends('layouts.app')

@section('page-title', 'My Profile')

@section('content')
<div class="max-w-6xl mx-auto pb-8" x-data="{ activeTab: '{{ session('status') === 'password-updated' ? 'security' : 'general' }}' }">
    
    {{-- Tabs Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6 font-medium text-sm text-gray-500 dark:text-gray-400">
        <ul class="flex overflow-x-auto gap-6 sm:gap-8 hide-scrollbar">
            <li>
                <button type="button" @click="activeTab = 'general'" 
                        class="pb-3 border-b-2 transition-colors whitespace-nowrap focus:outline-none"
                        :class="activeTab === 'general' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent hover:text-gray-800 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'">
                    General Information
                </button>
            </li>
            <li>
                <button type="button" @click="activeTab = 'address'" 
                        class="pb-3 border-b-2 transition-colors whitespace-nowrap focus:outline-none"
                        :class="activeTab === 'address' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent hover:text-gray-800 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'">
                    Address Details
                </button>
            </li>
            <li>
                <button type="button" @click="activeTab = 'social'" 
                        class="pb-3 border-b-2 transition-colors whitespace-nowrap focus:outline-none"
                        :class="activeTab === 'social' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent hover:text-gray-800 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'">
                    Social Links
                </button>
            </li>
            <li>
                <button type="button" @click="activeTab = 'security'" 
                        class="pb-3 border-b-2 transition-colors whitespace-nowrap focus:outline-none"
                        :class="activeTab === 'security' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-500 dark:text-indigo-400' : 'border-transparent hover:text-gray-800 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'">
                    Security
                </button>
            </li>
        </ul>
    </div>

    {{-- Content Area --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-xl border border-gray-100 dark:border-gray-750 overflow-hidden">
        
        {{-- General Tab --}}
        <div x-show="activeTab === 'general'" x-cloak style="display: none;" class="p-6 sm:p-8">
            <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-2xl">
                @csrf
                @method('patch')

                {{-- Avatar --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profile Avatar</label>
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 rounded-full border-2 border-indigo-100 dark:border-indigo-900 bg-gray-100 dark:bg-gray-700 overflow-hidden flex items-center justify-center flex-shrink-0">
                            @if($user->profile?->avatar)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($user->profile->avatar) }}" alt="Avatar" class="w-full h-full object-cover">
                            @else
                                <span class="text-2xl font-bold text-gray-400">{{ str($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => str($part)->substr(0, 1))->join('') }}</span>
                            @endif
                        </div>
                        <div>
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 dark:text-gray-400">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG, PNG, GIF or SVG. Max size 2MB.</p>
                        </div>
                    </div>
                    @error('avatar')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" />
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" />
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $user->profile?->phone) }}" class="w-full sm:max-w-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" />
                    @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio / About Me</label>
                    <textarea id="bio" name="bio" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">{{ old('bio', $user->profile?->bio) }}</textarea>
                    @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 hover:bg-indigo-700 transition">
                        Save Changes
                    </button>
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-green-600 dark:text-green-400 font-medium">Saved successfully.</p>
                    @endif
                </div>
            </form>
        </div>

        {{-- Address Tab --}}
        <div x-show="activeTab === 'address'" x-cloak style="display: none;" class="p-6 sm:p-8">
            <form method="post" action="{{ route('profile.update') }}" class="space-y-6 max-w-2xl">
                @csrf
                @method('patch')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="address_line1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" value="{{ old('address_line1', $user->profile?->address_line1) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm" />
                    </div>
                    <div>
                        <label for="address_line2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address Line 2 (Optional)</label>
                        <input type="text" id="address_line2" name="address_line2" value="{{ old('address_line2', $user->profile?->address_line2) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                        <input type="text" id="city" name="city" value="{{ old('city', $user->profile?->city) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm" />
                    </div>
                    <div>
                        <label for="state_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State / Region</label>
                        <input type="text" id="state_region" name="state_region" value="{{ old('state_region', $user->profile?->state_region) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm" />
                    </div>
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Postal / Zip Code</label>
                        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->profile?->postal_code) }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm" />
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                        <select id="country" name="country" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                            <option value="">Select a country...</option>
                            <option value="US" @selected(old('country', $user->profile?->country) === 'US')>United States</option>
                            <option value="GB" @selected(old('country', $user->profile?->country) === 'GB')>United Kingdom</option>
                            <option value="IN" @selected(old('country', $user->profile?->country) === 'IN')>India</option>
                            <!-- other options could be added dynamically -->
                            <option value="CA" @selected(old('country', $user->profile?->country) === 'CA')>Canada</option>
                            <option value="AU" @selected(old('country', $user->profile?->country) === 'AU')>Australia</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-white shadow-sm hover:bg-indigo-700 transition">
                        Update Address
                    </button>
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-green-600 dark:text-green-400 font-medium">Saved successfully.</p>
                    @endif
                </div>
            </form>
        </div>

        {{-- Social Links Tab --}}
        <div x-show="activeTab === 'social'" x-cloak style="display: none;" class="p-6 sm:p-8">
            <form method="post" action="{{ route('profile.update') }}" class="space-y-6 max-w-2xl">
                @csrf
                @method('patch')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">LinkedIn Profile</label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </div>
                            <input type="url" name="social_links[linkedin]" value="{{ old('social_links.linkedin', $user->profile?->social_links['linkedin'] ?? '') }}" class="pl-10 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://linkedin.com/in/username">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Twitter Target</label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </div>
                            <input type="url" name="social_links[twitter]" value="{{ old('social_links.twitter', $user->profile?->social_links['twitter'] ?? '') }}" class="pl-10 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://twitter.com/username">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Facebook Profile</label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </div>
                            <input type="url" name="social_links[facebook]" value="{{ old('social_links.facebook', $user->profile?->social_links['facebook'] ?? '') }}" class="pl-10 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://facebook.com/username">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-white shadow-sm hover:bg-indigo-700 transition">
                        Update Socials
                    </button>
                    @if (session('status') === 'profile-updated')
                        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="text-sm text-green-600 dark:text-green-400 font-medium">Saved successfully.</p>
                    @endif
                </div>
            </form>
        </div>

        {{-- Security / Password Tab --}}
        <div x-show="activeTab === 'security'" x-cloak style="display: none;" class="p-6 sm:p-8">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
                
                <hr class="my-8 border-gray-200 dark:border-gray-700">
                
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>

<style>
/* Utility class to hide scrollbar but allow sliding */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
@endsection
