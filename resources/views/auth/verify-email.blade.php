<x-guest-layout>
    <div class="et-auth__header">
        <h2>{{ __('Verify your email') }}</h2>
        <p>{{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="et-alert et-alert--success" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="et-auth-form__meta" style="margin-top:1.25rem;gap:1rem;flex-wrap:wrap">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="et-btn et-btn--primary">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="et-btn et-btn--ghost">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
