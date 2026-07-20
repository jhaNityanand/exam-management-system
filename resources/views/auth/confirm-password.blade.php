<x-guest-layout>
    <div class="et-auth__header">
        <h2>{{ __('Confirm password') }}</h2>
        <p>{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="et-form et-form--stack et-auth-form">
        @csrf

        <label class="et-field">
            <span>{{ __('Password') }}</span>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="et-field-error" />
        </label>

        <button type="submit" class="et-btn et-btn--primary et-btn--block et-btn--lg">
            {{ __('Confirm') }}
        </button>
    </form>
</x-guest-layout>
