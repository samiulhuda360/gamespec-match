@extends('layouts.app')

@section('title', ($text !== '' ? $text.' – ' : '').'Ask GameSpec – find games in plain words')
@section('meta_description', 'Describe the game you want in plain words, like "co-op survival under $20 my PC can run", and get matching Steam games.')

@push('styles')
<style>
  .ask{max-width:1200px; margin:0 auto; padding:32px 16px 80px}
  .ask-head{text-align:center; margin-bottom:22px}
  .ask-head h1{font-size:clamp(26px, 4vw, 38px); font-weight:900}
  .ask-head p{color:var(--muted); margin-top:6px}
  .ask-form{display:flex; gap:10px; max-width:760px; margin:0 auto}
  .ask-form input{flex:1; min-width:0; padding:14px 16px; border-radius:12px; border:1px solid var(--line);
    background:rgba(255,255,255,.05); color:var(--text); font:inherit; font-size:16px}
  .ask-form input:focus{outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(110,168,255,.18)}
  .examples{display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin:14px auto 0; max-width:860px}
  .examples a{font-size:13px; color:var(--muted); border:1px solid var(--line); border-radius:999px; padding:6px 12px; text-decoration:none}
  .examples a:hover{color:var(--text); border-color:var(--primary)}
  .understood{margin:26px 0 8px; display:flex; flex-wrap:wrap; gap:8px; align-items:center}
  .understood .label{color:var(--muted); font-size:13px; margin-right:4px}
  .chip{font-size:13px; padding:5px 11px; border-radius:999px; background:rgba(110,168,255,.12); color:var(--text); border:1px solid rgba(110,168,255,.25)}
  .src{font-size:11px; font-weight:700; padding:3px 8px; border-radius:999px; background:rgba(47,209,197,.14); color:var(--accent)}
  .src.rules{background:rgba(255,255,255,.08); color:var(--muted)}
  .ask-meta{color:var(--muted); font-size:13px; margin-bottom:18px}
  .ask .grid{margin-top:8px}
  .why{display:flex; flex-wrap:wrap; gap:6px; margin-top:auto; padding-top:10px}
  .verdict{font-size:12px; font-weight:700; padding:5px 10px; border-radius:8px; margin-top:8px; display:inline-block}
  .v-yes{background:rgba(78,255,187,.1); color:var(--green)}
  .v-min{background:rgba(255,205,110,.1); color:var(--orange)}
  .v-no{background:rgba(255,107,107,.1); color:var(--red)}
  .pc-line{max-width:760px; margin:12px auto 0; text-align:center; font-size:13px; color:var(--muted)}
  .pc-line a{color:var(--primary)}
</style>
@endpush

@section('content')
<main class="ask">
  <div class="ask-head">
    <h1>Ask GameSpec</h1>
    <p>Describe the game you want. Price, players, genre, “runs on my PC” and “like …” all work.</p>
  </div>

  <form class="ask-form" method="GET" action="{{ route('ask') }}" role="search">
    <input type="search" name="q" value="{{ $text }}" maxlength="300" autocomplete="off"
           placeholder="e.g. co-op survival games under $20 my laptop can run" aria-label="Describe the game you want" @if($text === '') autofocus @endif>
    <button class="btn-solid" type="submit">Search</button>
  </form>

  <p class="pc-line">
    @if ($pc)
      Checking against <b>{{ $pc->profile_name }}</b> ({{ $pc->gpu_model ?? $pc->gpu }}). <a href="{{ route('hardware.add-specs') }}">Change</a>
    @else
      <a href="{{ route('hardware.add-specs') }}">Add your PC</a> to see which results it can run.
    @endif
  </p>

  @if ($query === null)
    <div class="examples">
      @foreach ($examples as $example)
        <a href="{{ route('ask', ['q' => $example]) }}">{{ $example }}</a>
      @endforeach
    </div>
  @else
    <div class="understood">
      <span class="label">Understood as</span>
      @forelse ($query->chips() as $chip)
        <span class="chip">{{ $chip }}</span>
      @empty
        <span class="chip">Everything (no filters found)</span>
      @endforelse
      <span class="src {{ $query->source === 'rules' ? 'rules' : '' }}" title="How the request was read">
        {{ $query->source === 'rules' ? 'read by rules' : 'read by AI' }}
      </span>
    </div>
    <div class="ask-meta">
      {{ $result['matches'] }} {{ \Illuminate\Support\Str::plural('game', $result['matches']) }} {{ $result['matches'] === 1 ? 'passes' : 'pass' }} your filters
      @if ($result['ranking'] === 'meaning') · closest in meaning first (embeddings) @elseif ($result['ranking'] === 'words') · most shared words first @endif
    </div>

    @foreach ($result['notes'] as $note)
      <div class="notice notice-warn">{{ $note }}</div>
    @endforeach

    @if ($result['games']->isEmpty())
      <div class="panel" style="text-align:center">
        <h2>No games match all of that</h2>
        <p class="sub">Try fewer filters, a higher price, or different words.</p>
      </div>
    @else
      <div class="grid">
        @foreach ($result['games'] as $app)
          <a class="game-card" href="{{ route('apps.show', $app) }}">
            <img class="thumb" loading="lazy" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text='.urlencode($app->name) }}" alt="{{ $app->name }}">
            <div class="p">
              <div class="title" style="font-weight:600">{{ $app->name }}</div>
              <div style="color:var(--muted); font-size:12px; margin-top:4px">
                {{ $app->genres->pluck('name')->take(3)->implode(' · ') }}
                @if ($app->release_date) · {{ $app->release_date->year }} @endif
              </div>
              @isset($app->verdict)
                @php($can = $app->verdict['can_run'])
                <span class="verdict {{ $can === 'yes_recommended' ? 'v-yes' : ($can === 'yes_minimum' ? 'v-min' : 'v-no') }}">
                  {{ ['yes_recommended' => '✓ Runs well on your PC', 'yes_minimum' => '◐ Runs at minimum settings', 'no' => '✕ Below minimum', 'unknown' => 'No requirements listed'][$can] ?? $can }}
                </span>
              @endisset
              <div class="why">
                @if ($app->is_free)
                  <span class="pill free">Free</span>
                @elseif ($app->pricing)
                  @if ($app->pricing->discount_percent > 0)<span class="pill discount">-{{ $app->pricing->discount_percent }}%</span>@endif
                  <span class="pill paid">${{ number_format($app->pricing->final_price / 100, 2) }}</span>
                @endif
                @if ($app->review_percentage !== null)
                  <span class="pill {{ $app->review_percentage >= 80 ? 'positive' : ($app->review_percentage >= 50 ? 'mixed' : 'negative') }}">{{ $app->review_percentage }}%</span>
                @endif
                @foreach ($query->features as $feature)
                  <span class="pill" style="background:rgba(47,209,197,.1); color:var(--accent)">{{ \App\Services\Search\SearchQuery::FEATURES[$feature]['label'] }}</span>
                @endforeach
              </div>
            </div>
          </a>
        @endforeach
      </div>
    @endif
  @endif
</main>
@endsection
