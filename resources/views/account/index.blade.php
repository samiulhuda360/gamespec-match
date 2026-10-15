@extends('layouts.app')

@section('title', 'Your account – GameSpecMatch')

@push('styles')
<style>
  .account{max-width:980px; margin:40px auto 80px; padding:0 16px}
  .account-head{display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:24px}
  .account-head h1{font-size:28px}
  .account-head p{color:var(--muted); margin-top:4px}
  .pc-grid{display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px}
  .pc{background:var(--card); border:1px solid var(--line); border-radius:14px; padding:18px; display:flex; flex-direction:column; gap:10px}
  .pc.active{border-color:var(--primary)}
  .pc h3{font-size:17px; display:flex; justify-content:space-between; gap:8px; align-items:center}
  .pc dl{display:grid; grid-template-columns:48px 1fr; gap:4px 10px; font-size:14px}
  .pc dt{color:var(--muted)}
  .pc .score{font-size:13px; color:var(--muted)}
  .pc .score b{color:var(--text); font-size:16px}
  .pc .actions{display:flex; gap:8px; flex-wrap:wrap; margin-top:auto}
  .tag{font-size:11px; font-weight:700; padding:3px 8px; border-radius:999px; background:rgba(110,168,255,.15); color:var(--primary)}
  .settings{margin-top:32px; display:flex; gap:12px; flex-wrap:wrap}
</style>
@endpush

@section('content')
<main class="account">
  <div class="account-head">
    <div>
      <h1>Hi, {{ $user->username }}</h1>
      <p>Your saved PCs. The active one is used for every “Can I run it?” check.</p>
    </div>
    <a href="{{ route('hardware.add-specs') }}" class="btn-solid">Add or edit a PC</a>
  </div>

  @if ($pcs->isEmpty())
    <div class="panel">
      <h2>No PCs yet</h2>
      <p class="sub">Add your PC once, by picking parts or describing it in your own words, and every game page shows whether it will run.</p>
      <a href="{{ route('hardware.add-specs') }}" class="btn-solid">Add your PC</a>
    </div>
  @else
    <div class="pc-grid">
      @foreach ($pcs as $pc)
        <article class="pc {{ $pc->id === $activeId ? 'active' : '' }}">
          <h3>{{ $pc->profile_name }} @if ($pc->id === $activeId)<span class="tag">Active</span>@endif</h3>
          <dl>
            <dt>CPU</dt><dd>{{ $pc->cpu_model ?? $pc->cpu ?? '—' }}</dd>
            <dt>GPU</dt><dd>{{ $pc->gpu_model ?? $pc->gpu ?? '—' }}</dd>
            <dt>RAM</dt><dd>{{ $pc->ram_gb ? $pc->ram_gb.' GB' : ($pc->ram ?? '—') }}</dd>
          </dl>
          <div class="score">Score <b>{{ number_format((float) $pc->overall_score) }}</b> · {{ $pc->tier }}</div>
          <div class="actions">
            @if ($pc->id !== $activeId)
              <a href="{{ route('hardware.edit', $pc->id) }}" class="btn-quiet">Make active</a>
            @endif
            <a href="{{ route('hardware.show', $pc->id) }}" class="btn-quiet">Games it runs</a>
            @if ($pc->id === $activeId)
              <a href="{{ route('upgrade') }}" class="btn-quiet">What to upgrade</a>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif

  <div class="settings">
    <a href="{{ route('profile.edit') }}" class="btn-quiet">Account settings</a>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn-quiet">Sign out</button>
    </form>
  </div>
</main>
@endsection
