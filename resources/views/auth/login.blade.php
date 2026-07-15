<x-guest-layout>
    <div class="et-auth__header">
        <h2>Welcome back</h2>
        <p>Sign in to continue your exam preparation.</p>
    </div>

    <x-auth-session-status class="et-alert et-alert--success" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="et-form et-form--stack et-auth-form">
        @csrf

        <label class="et-field">
            <span>{{ __('Email') }}</span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="et-field-error" />
        </label>

        <label class="et-field">
            <span>{{ __('Password') }}</span>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="et-field-error" />
        </label>

        <div class="et-auth-form__meta">
            <label class="et-check">
                <input id="remember_me" type="checkbox" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="et-link">{{ __('Forgot password?') }}</a>
            @endif
        </div>

        <button type="submit" class="et-btn et-btn--primary et-btn--block et-btn--lg">{{ __('Log in') }}</button>
    </form>

    <p class="et-auth__switch">
        New to Examtube?
        <a href="{{ route('register') }}" class="et-link">Create an account</a>
    </p>
</x-guest-layout>
