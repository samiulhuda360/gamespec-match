<x-guest-layout title="Sign in">
    <h1>Sign in</h1>
    <p class="sub">Your saved PCs and wishlist follow you to any device.</p>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="field">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="check">
            <input id="remember_me" type="checkbox" name="remember"> {{ __('Remember me') }}
        </label>

        <div class="form-actions">
            @if (Route::has('password.request'))
                <a class="text-link" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
            <x-primary-button>{{ __('Sign in') }}</x-primary-button>
        </div>
    </form>

    <p class="sub" style="margin:18px 0 0">New here? <a class="text-link" href="{{ route('register') }}">Create an account</a></p>
</x-guest-layout>
