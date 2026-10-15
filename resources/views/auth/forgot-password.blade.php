<x-guest-layout title="Reset your password">
    <h1>Reset your password</h1>
    <p class="sub">{{ __('Enter your email address and we will send you a link to choose a new password.') }}</p>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="form-actions">
            <a class="text-link" href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
            <x-primary-button>{{ __('Email reset link') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
