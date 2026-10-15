<x-guest-layout title="Create an account">
    <h1>Create an account</h1>
    <p class="sub">Save your PC once and check any game against it. A PC you already added in this browser moves to your account.</p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" type="text" name="username" :value="old('username')" required autofocus autocomplete="nickname" />
            <x-input-error :messages="$errors->get('username')" />
        </div>

        <div class="field">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="field">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="field">
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="form-actions">
            <a class="text-link" href="{{ route('login') }}">{{ __('Already registered?') }}</a>
            <x-primary-button>{{ __('Create account') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
