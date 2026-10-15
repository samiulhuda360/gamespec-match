<x-guest-layout title="Verify your email">
    <h1>Verify your email</h1>
    <p class="sub">{{ __('Thanks for signing up! Please click the link we just emailed you. If it did not arrive, we can send another.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <div class="notice notice-ok">{{ __('A new verification link has been sent to your email address.') }}</div>
    @endif

    <div class="form-actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>{{ __('Resend verification email') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-link">{{ __('Sign out') }}</button>
        </form>
    </div>
</x-guest-layout>
