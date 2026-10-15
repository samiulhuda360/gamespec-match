{{-- Sign-in pages use the site layout, so they need no separate CSS build. --}}
@extends('layouts.app')

@section('title', ($title ?? 'Your account').' – GameSpecMatch')

@push('styles')
<style>
  .auth-wrap{max-width:440px; margin:56px auto 80px; padding:0 16px}
</style>
@endpush

@section('content')
<main class="auth-wrap">
  <div class="panel">
    {{ $slot }}
  </div>
</main>
@endsection
