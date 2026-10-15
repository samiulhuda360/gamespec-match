
{{-- ============================================ --}}
{{-- resources/views/hardware/show.blade.php --}}
{{-- ============================================ --}}
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $hardware->profile_name }} - Compatible Games</title>
  <style>
    :root{
      --bg:#0b1220; --card:#0f1a2f; --text:#e9eefc; --muted:#a9b4d0;
      --line:rgba(255,255,255,.08); --brand:#6ea8ff; --green:#4effbb; --orange:#ffcd6e;
    }
    *{box-sizing:border-box}
    body{
      margin:0; padding:24px 14px;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: radial-gradient(1200px 600px at 20% 0%, rgba(110,168,255,.18), transparent 60%), var(--bg);
      color:var(--text);
    }
    .wrap{max-width:1200px; margin:0 auto}
    .back{
      display:inline-flex; gap:6px; padding:10px 14px; border:1px solid var(--line);
      border-radius:12px; background:rgba(255,255,255,.04); color:var(--text);
      text-decoration:none; margin-bottom:20px;
    }
    .hero{
      border:1px solid var(--line); border-radius:18px; padding:20px;
      background:linear-gradient(135deg, rgba(110,168,255,.12), rgba(47,209,197,.08));
      border-color:rgba(110,168,255,.3);
    }
    h1{margin:0 0 10px 0; font-size:28px}
    .specs{
      display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
      gap:12px; margin-top:16px;
    }
    .spec-box{
      background:rgba(0,0,0,.2); padding:12px; border-radius:10px;
      border:1px solid rgba(255,255,255,.1);
    }
    .spec-label{font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px}
    .spec-value{font-size:16px; font-weight:600; margin-top:4px}
    .score-badge{
      display:inline-flex; align-items:center; gap:6px;
      padding:6px 12px; background:var(--green); color:#000;
      border-radius:999px; font-size:14px; font-weight:700; margin-top:8px;
    }
    .grid{
      display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
      gap:14px; margin-top:20px;
    }
    .card{
      border:1px solid var(--line); border-radius:14px; overflow:hidden;
      background:rgba(255,255,255,.04); text-decoration:none; color:inherit;
      transition:transform .12s ease;
    }
    .card:hover{transform:translateY(-2px); border-color:rgba(110,168,255,.4)}
    .thumb{width:100%; height:140px; object-fit:cover; background:rgba(255,255,255,.05)}
    .p{padding:14px}
    .title{font-weight:700; font-size:16px; line-height:1.3}
    .meta{font-size:13px; color:var(--muted); margin-top:6px}
    .pill{
      display:inline-flex; padding:4px 10px; border-radius:999px;
      border:1px solid rgba(255,255,255,.1); font-size:12px; margin-top:8px;
    }
    .pill.yes{background:rgba(78,255,187,.15); border-color:rgba(78,255,187,.3); color:var(--green)}
    .pill.maybe{background:rgba(255,205,110,.15); border-color:rgba(255,205,110,.3); color:var(--orange)}
    h2{margin:30px 0 16px 0; font-size:22px}
    .empty{
      padding:40px 20px; text-align:center; border:1px dashed var(--line);
      border-radius:14px; color:var(--muted);
    }
  </style>
</head>
<body>
<div class="wrap">
  <a href="{{ route('apps.index') }}" class="back">← Back to Games</a>

  <div class="hero">
    <h1>🖥️ {{ $hardware->profile_name }}</h1>
    <div style="color:var(--muted); font-size:14px">
      Your system score: <b style="color:var(--text)">{{ number_format($hardware->overall_score) }}</b> / 1000
    </div>
    
    <div class="specs">
      <div class="spec-box">
        <div class="spec-label">CPU</div>
        <div class="spec-value">{{ $hardware->cpu }}</div>
        <div style="font-size:12px; color:var(--muted); margin-top:2px">
          Score: {{ number_format($hardware->cpu_score) }}
        </div>
      </div>
      <div class="spec-box">
        <div class="spec-label">RAM</div>
        <div class="spec-value">{{ $hardware->ram }}</div>
        <div style="font-size:12px; color:var(--muted); margin-top:2px">
          Score: {{ number_format($hardware->ram_score) }}
        </div>
      </div>
      <div class="spec-box">
        <div class="spec-label">GPU</div>
        <div class="spec-value">{{ $hardware->gpu }}</div>
        <div style="font-size:12px; color:var(--muted); margin-top:2px">
          Score: {{ number_format($hardware->gpu_score) }}
        </div>
      </div>
      <div class="spec-box">
        <div class="spec-label">Storage</div>
        <div class="spec-value">{{ $hardware->storage }}</div>
        <div style="font-size:12px; color:var(--muted); margin-top:2px">
          Score: {{ number_format($hardware->storage_score) }}
        </div>
      </div>
    </div>
  </div>

  <h2>✅ Games You Can Run ({{ $compatibleGames->count() }})</h2>

  @if($compatibleGames->count() === 0)
    <div class="empty">
      <div style="font-size:40px; margin-bottom:10px">🎮</div>
      <div>No compatible games found with your current specs.</div>
      <div style="margin-top:8px">Try updating your hardware or check back later!</div>
    </div>
  @else
    <div class="grid">
      @foreach($compatibleGames as $app)
        <a class="card" href="{{ route('apps.show', $app->app_id) }}">
          <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300?text=No+Image' }}" alt="">
          <div class="p">
            <div class="title">{{ $app->name }}</div>
            <div class="meta">
              {{ $app->type }} · {{ $app->release_date }}
            </div>
            @php
              $req = $app->sysRequirements->where('platform', $hardware->platform)->first();
              $canRunRec = $req && $hardware->overall_score >= ($req->rec_overall_score ?? 0);
            @endphp
            @if($canRunRec)
              <span class="pill yes">✅ Recommended Settings</span>
            @else
              <span class="pill maybe">⚠️ Minimum Settings</span>
            @endif
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>
</body>
</html>