<x-guest-layout>
    <div class="et-auth__header">
        <h2>Reset your password</h2>
        <p>Enter your email and we’ll send a secure reset link.</p>
    </div>

    <x-auth-session-status class="et-alert et-alert--success" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="et-form et-form--stack et-auth-form">
        @csrf

        <label class="et-field">
            <span>{{ __('Email') }}</span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="et-field-error" />
        </label>

        <button type="submit" class="et-btn et-btn--primary et-btn--block et-btn--lg">{{ __('Email reset link') }}</button>
    </form>

    <p class="et-auth__switch">
        <a href="{{ route('login') }}" class="et-link">← Back to sign in</a>
    </p>
</x-guest-layout>
