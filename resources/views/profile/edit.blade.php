@extends('layouts.app')

@section('title', 'Account settings – GameSpecMatch')

@push('styles')
<style>
  .settings-wrap{max-width:560px; margin:40px auto 80px; padding:0 16px; display:grid; gap:20px}
  .settings-wrap > a{font-size:14px}
</style>
@endpush

@section('content')
<main class="settings-wrap">
  <a class="text-link" href="{{ route('dashboard') }}">← Back to your account</a>

  <section class="panel">
    <h2>Profile</h2>
    <p class="sub">Your username and email address.</p>
    @if (session('status') === 'profile-updated')
      <div class="notice notice-ok">Saved.</div>
    @endif
    <form method="POST" action="{{ route('profile.update') }}">
      @csrf
      @method('patch')
      <div class="field">
        <x-input-label for="username" value="Username" />
        <x-text-input id="username" name="username" type="text" :value="old('username', $user->username)" required autocomplete="nickname" />
        <x-input-error :messages="$errors->get('username')" />
      </div>
      <div class="field">
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" />
      </div>
      <x-primary-button>Save</x-primary-button>
    </form>
  </section>

  <section class="panel">
    <h2>Password</h2>
    <p class="sub">Use a long, random password.</p>
    @if (session('status') === 'password-updated')
      <div class="notice notice-ok">Password changed.</div>
    @endif
    <form method="POST" action="{{ route('password.update') }}">
      @csrf
      @method('put')
      <div class="field">
        <x-input-label for="current_password" value="Current password" />
        <x-text-input id="current_password" name="current_password" type="password" autocomplete="current-password" />
        <x-input-error :messages="$errors->updatePassword->get('current_password')" />
      </div>
      <div class="field">
        <x-input-label for="new_password" value="New password" />
        <x-text-input id="new_password" name="password" type="password" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password')" />
      </div>
      <div class="field">
        <x-input-label for="new_password_confirmation" value="Confirm password" />
        <x-text-input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
      </div>
      <x-primary-button>Change password</x-primary-button>
    </form>
  </section>

  <section class="panel">
    <h2>Delete account</h2>
    <p class="sub">This removes your account and the PCs saved to it.</p>
    <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Delete your account for good?')">
      @csrf
      @method('delete')
      <div class="field">
        <x-input-label for="delete_password" value="Password" />
        <x-text-input id="delete_password" name="password" type="password" required autocomplete="current-password" />
        <x-input-error :messages="$errors->userDeletion->get('password')" />
      </div>
      <x-danger-button>Delete account</x-danger-button>
    </form>
  </section>
</main>
@endsection
