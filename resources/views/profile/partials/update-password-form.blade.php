<section class="profile-password-section">
    <header>
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5" data-password-form novalidate>
        @csrf
        @method('put')

        <div class="profile-field" data-password-host>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" placeholder="Enter your current password" data-password-field="current" />
            <p class="profile-field-error" data-password-error="current">@foreach ($errors->updatePassword->get('current_password') as $message){{ $message }}@endforeach</p>
        </div>

        <div class="profile-field" data-password-host>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="At least 8 characters" data-password-field="new" />
            <p class="profile-field-hint">Use 8+ characters with uppercase, lowercase, and a number.</p>
            <p class="profile-field-error" data-password-error="new">@foreach ($errors->updatePassword->get('password') as $message){{ $message }}@endforeach</p>
        </div>

        <div class="profile-field" data-password-host>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" placeholder="Repeat your new password" data-password-field="confirmation" />
            <p class="profile-field-error" data-password-error="confirmation">@foreach ($errors->updatePassword->get('password_confirmation') as $message){{ $message }}@endforeach</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="profile-btn profile-btn--primary">{{ __('Update password') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600 dark:text-slate-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
