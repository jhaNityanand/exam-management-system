<x-guest-layout>
    <div class="et-auth__header">
        <h2>Choose a new password</h2>
        <p>Enter a strong password to secure your Examtube account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="et-form et-form--stack et-auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label class="et-field">
            <span>{{ __('Email') }}</span>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="et-field-error" />
        </label>

        <label class="et-field">
            <span>{{ __('Password') }}</span>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="et-field-error" />
        </label>

        <label class="et-field">
            <span>{{ __('Confirm Password') }}</span>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="et-field-error" />
        </label>

        <button type="submit" class="et-btn et-btn--primary et-btn--block et-btn--lg">{{ __('Reset Password') }}</button>
    </form>
</x-guest-layout>
