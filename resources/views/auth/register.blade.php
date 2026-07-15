<x-guest-layout>
    <div class="et-auth__header">
        <h2>Create your account</h2>
        <p>Join Examtube.in and start practicing with structured exams.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="et-form et-form--stack et-auth-form">
        @csrf

        <label class="et-field">
            <span>{{ __('Name') }}</span>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Your full name">
            <x-input-error :messages="$errors->get('name')" class="et-field-error" />
        </label>

        <label class="et-field">
            <span>{{ __('Email') }}</span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@example.com">
            <x-input-error :messages="$errors->get('email')" class="et-field-error" />
        </label>

        <label class="et-field">
            <span>{{ __('Password') }}</span>
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Create a strong password">
            <x-input-error :messages="$errors->get('password')" class="et-field-error" />
        </label>

        <label class="et-field">
            <span>{{ __('Confirm Password') }}</span>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="et-field-error" />
        </label>

        <button type="submit" class="et-btn et-btn--primary et-btn--block et-btn--lg">{{ __('Create account') }}</button>
    </form>

    <p class="et-auth__switch">
        Already registered?
        <a href="{{ route('login') }}" class="et-link">Sign in</a>
    </p>
</x-guest-layout>
