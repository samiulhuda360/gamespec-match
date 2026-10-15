@extends('layouts.app')

@section('title', 'What should I upgrade? – GameSpecMatch')

@push('styles')
<style>
  .upg{max-width:1000px; margin:0 auto; padding:32px 16px 80px; display:grid; gap:20px}
  .upg h1{font-size:clamp(24px, 4vw, 34px); font-weight:900}
  .upg .lead{color:var(--muted)}
  .pc-parts{display:flex; flex-wrap:wrap; gap:8px; margin-top:12px}
  .pc-parts span{font-size:13px; padding:6px 11px; border-radius:999px; border:1px solid var(--line); background:rgba(255,255,255,.04)}
  .now{display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px}
  .now div{background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px}
  .now b{display:block; font-size:28px}
  .now span{color:var(--muted); font-size:13px}
  .summary{line-height:1.6; font-size:16px}
  .src{font-size:11px; font-weight:700; padding:3px 8px; border-radius:999px; background:rgba(47,209,197,.14); color:var(--accent); margin-left:6px; vertical-align:middle}
  .src.rules{background:rgba(255,255,255,.08); color:var(--muted)}
  .bars{display:grid; gap:10px}
  .bar{display:grid; grid-template-columns:120px 1fr 48px; gap:12px; align-items:center; font-size:14px}
  .bar .track{height:10px; border-radius:999px; background:rgba(255,255,255,.06); overflow:hidden}
  .bar .fill{height:100%; background:linear-gradient(90deg, var(--orange), var(--red))}
  .opts{width:100%; border-collapse:collapse; font-size:14px}
  .opts th{text-align:left; color:var(--muted); font-weight:600; padding:8px 10px; border-bottom:1px solid var(--line)}
  .opts td{padding:12px 10px; border-bottom:1px solid var(--line); vertical-align:top}
  .gain{font-weight:800; color:var(--green)}
  .opts a{color:var(--primary); text-decoration:none}
  .needs{display:grid; gap:10px}
  .need{display:grid; grid-template-columns:110px 1fr; gap:10px; font-size:14px}
  .need b{color:var(--muted); font-weight:600}
  @media(max-width:700px){
    .now{grid-template-columns:1fr}
    .bar{grid-template-columns:90px 1fr 40px}
    .opts .examples{display:none}
  }
</style>
@endpush

@section('content')
@php($names = ['gpu' => 'Graphics card', 'cpu' => 'Processor', 'ram' => 'Memory'])
<main class="upg">
  <div>
    <h1>What should I upgrade?</h1>
    <p class="lead">Your PC is checked against {{ $advice['games'] }} games, then again with each common upgrade swapped in.</p>
    <div class="pc-parts">
      <span>{{ $pc->profile_name }}</span>
      <span>{{ $pc->cpu_model ?? $pc->cpu }}</span>
      <span>{{ $pc->gpu_model ?? $pc->gpu }}</span>
      <span>{{ (int) $pc->ram_score }} GB RAM</span>
      <span>{{ $tier }}</span>
    </div>
  </div>

  <div class="now">
    <div><b>{{ $advice['baseline']['recommended'] }}</b><span>games at recommended settings</span></div>
    <div><b>{{ $advice['baseline']['playable'] }}</b><span>playable at minimum or better</span></div>
    <div><b>{{ $advice['games'] - $advice['baseline']['playable'] }}</b><span>below minimum</span></div>
  </div>

  <section class="panel">
    <h2>In short <span class="src {{ $summary['source'] === 'ai' ? '' : 'rules' }}">{{ $summary['source'] === 'ai' ? 'written by AI from the numbers below' : 'written from the numbers below' }}</span></h2>
    <p class="summary">{{ $summary['text'] }}</p>
  </section>

  @if ($advice['target'])
    @php($t = $advice['target'])
    <section class="panel">
      <h2>{{ $t['name'] }} at recommended settings</h2>
      @if ($t['needs'] === [])
        <p class="sub">Your PC already meets the recommended requirements. <a class="text-link" href="{{ route('apps.show', $t['slug']) }}">Back to the game</a></p>
      @else
        <p class="sub">What the game recommends, against your parts:</p>
        <div class="needs">
          @foreach ($t['needs'] as $part => $need)
            <div class="need">
              <b>{{ $names[$part] }}</b>
              <div>
                Needs {{ $t['requirements'][$part] ?: 'more' }}.
                @if ($need['suggestion']) <strong>{{ $need['suggestion'] }}</strong> or faster would do it. @else None of the common upgrades listed here is enough. @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </section>
  @endif

  <section class="panel">
    <h2>What holds your PC back</h2>
    <p class="sub">Games you can't run at recommended settings, by the part that falls short (a game can count for more than one).</p>
    @php($maxB = max(1, max($advice['bottlenecks'])))
    <div class="bars">
      @foreach ($advice['bottlenecks'] as $part => $count)
        <div class="bar">
          <span>{{ $names[$part] }}</span>
          <div class="track"><div class="fill" style="width: {{ round(100 * $count / $maxB) }}%"></div></div>
          <span>{{ $count }}</span>
        </div>
      @endforeach
    </div>
  </section>

  <section class="panel">
    <h2>Upgrades that help most</h2>
    @if ($advice['options'] === [])
      <p class="sub">None of the common upgrades would let you run more of these games. Your PC already covers this catalogue well.</p>
    @else
      <table class="opts">
        <thead><tr><th>Upgrade</th><th>More games at recommended</th><th>More playable</th><th class="examples">For example</th></tr></thead>
        <tbody>
          @foreach ($advice['options'] as $o)
            <tr>
              <td><b>{{ $o['part'] }}</b><br><span style="color:var(--muted); font-size:12px">{{ $names[$o['component']] }}</span></td>
              <td class="gain">+{{ $o['gain_recommended'] }}</td>
              <td>+{{ $o['gain_playable'] }}</td>
              <td class="examples">
                @foreach ($o['examples'] as $g)
                  <a href="{{ route('apps.show', $g['slug']) }}">{{ $g['name'] }}</a>@if (! $loop->last), @endif
                @endforeach
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <p class="sub" style="margin:12px 0 0">Counts swap one part at a time on the same scores the game pages use. Prices are not considered.</p>
    @endif
  </section>
</main>
@endsection
