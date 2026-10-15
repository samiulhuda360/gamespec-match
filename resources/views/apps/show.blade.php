@extends('layouts.app')

@section('title', $app->name . ' System Requirements – Can I Run It? | GameSpecMatch')

@section('meta_description', 'Check if your PC can run '.$app->name.'. See minimum and recommended system requirements (CPU, GPU, RAM, storage) for Windows, macOS, and Linux. Compare specs instantly on GameSpecMatch.')

@section('canonical', url()->current())

@section('og_type', 'article')
@section('og_title', $app->name . ' System Requirements – GameSpecMatch')
@section('og_description', 'Can I run '.$app->name.'? Compare your PC specs vs the official minimum requirements.')
@section('og_image', $app->header_image ?: asset('images/og-default.png'))

@push('jsonld')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'VideoGame',
    'name' => $app->name,
    'description' => $app->short_description ?? '',
    'image' => $app->header_image ?? '',
    'url' => route('apps.show', $app->slug),
    'datePublished' => $app->release_date?->format('Y-m-d'),
    'author' => $app->developers && is_array($app->developers) && count($app->developers) > 0 ? [
        '@type' => 'Organization',
        'name' => $app->developers[0]
    ] : null,
    'publisher' => $app->publishers && is_array($app->publishers) && count($app->publishers) > 0 ? [
        '@type' => 'Organization',
        'name' => $app->publishers[0]
    ] : null,
    'genre' => $app->genres && $app->genres->isNotEmpty() ? $app->genres->pluck('name')->toArray() : null,
    'offers' => $app->pricing ? [
        '@type' => 'Offer',
        'price' => number_format($app->pricing->final_price / 100, 2, '.', ''),
        'priceCurrency' => $app->pricing->currency ?? 'USD',
        'availability' => 'https://schema.org/InStock',
        'url' => route('apps.show', $app->slug)
    ] : null,
    'aggregateRating' => $app->reviews && $app->reviews->total_reviews > 0 ? [
        '@type' => 'AggregateRating',
        'ratingValue' => $app->reviews->total_positive > 0 ? number_format(($app->reviews->total_positive / $app->reviews->total_reviews) * 5, 1) : 0,
        'ratingCount' => $app->reviews->total_reviews,
        'bestRating' => 5,
        'worstRating' => 0
    ] : null,
    'operatingSystem' => 'Windows' . ($app->sysRequirements->contains('platform', 'mac') ? ', macOS' : '') . ($app->sysRequirements->contains('platform', 'linux') ? ', Linux' : '')
]), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@push('styles')
<style>
/* ===============================
   SHOW PAGE – SELF CONTAINED CSS
   =============================== */

.wrap{max-width:1200px;margin:0 auto;}
h1{font-size:32px;margin:0}
h2{font-size:18px;margin:0 0 8px 0}
h3{margin:0 0 10px 0}
a{color:var(--primary);text-decoration:none}
a:hover{text-decoration:underline}
.muted{color:var(--muted);font-size:13px;line-height:1.45}

.topbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:14px; /* Reduced from 16px */
  gap:10px;
  flex-wrap:wrap;
}
.back{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 14px;border:1px solid var(--line);border-radius:12px;
  background:rgba(255,255,255,.04);color:var(--text);
}

/* Hero */
.hero{
  border:1px solid var(--line);
  border-radius:18px;
  overflow:hidden;
  background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
}
.hero img{width:100%;height:240px;object-fit:cover}
.hero .p{padding:18px}

.meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.pill{
  font-size:12px;padding:4px 12px;border-radius:999px;
  border:1px solid rgba(255,255,255,.15);
}
.pill.free{color:var(--green);border-color:rgba(78,255,187,.3)}
.pill.paid{color:var(--orange);border-color:rgba(255,205,110,.3)}

/* Make hero-cta buttons more compact */
.hero-cta{
  display:flex;
  gap:8px; /* Reduced from 10px */
  flex-wrap:wrap;
  margin-top:0; /* Removed extra margin */
}

.cta-btn.big{
  width:auto;
  padding:12px 16px;
  font-size:14px;
  border-radius:12px;
  cursor:pointer;
  position:relative;
  z-index:10;
  pointer-events:auto;
}

.cta-secondary{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.04);
  color:var(--text);
  font-size:14px;
  cursor:pointer;
  position:relative;
  z-index:10;
  pointer-events:auto;
}

/* Stats */
.stats{
  margin-top:14px; /* Reduced from 16px */
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); /* Reduced from 240px */
  gap:12px; /* Reduced from 14px */
}
/* Reduce stats card padding */
.stat-card{
  border:1px solid var(--line);
  border-radius:14px;
  padding:14px; /* Reduced from 16px */
  background:rgba(255,255,255,.04);
}
.stat-value{font-size:26px;font-weight:700}
.progress-bar{
  margin-top:10px;height:6px;background:rgba(255,255,255,.08);
  border-radius:999px;overflow:hidden;
}
.progress-fill{height:100%}

/* Main grid */
.grid{
  margin-top:14px; /* Reduced from 16px */
  display:grid;
  grid-template-columns:1.45fr .55fr;
  gap:14px; /* Reduced from 16px */
}
@media(max-width:900px){ .grid{grid-template-columns:1fr} }

.card{
  border:1px solid var(--line);
  border-radius:16px;
  padding:14px; /* Reduced from 16px */
  background:rgba(255,255,255,.04);
}

/* Requirements */
.req{
  margin-top:10px; /* Reduced from 12px */
  padding:12px; /* Reduced from 14px */
  border-radius:14px;
  border:1px solid rgba(255,255,255,.08);
  background:rgba(255,255,255,.02);
}
.req-tabs{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 14px 0;}
.req-tab{
  padding:8px 12px;border-radius:999px;border:1px solid var(--line);
  background:rgba(255,255,255,.03);color:var(--text);
  cursor:pointer;font-size:13px;
}
.req-tab.active{border-color:rgba(255,255,255,.20);background:rgba(255,255,255,.06);}

.req-grid{
  display:grid;
  grid-template-columns:120px 1fr;
  gap:8px 12px;
  font-size:14px;
}
.req-grid div:nth-child(odd){color:var(--muted);}

/* Screenshots */
.shots{
  margin-top:10px;
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(160px,1fr));
  gap:10px;
}
.shots img{
  width:100%;height:110px;object-fit:cover;
  border-radius:12px;border:1px solid rgba(255,255,255,.1);
}

/* Sidebar */
.side-sticky{position:sticky;top:16px;}
/* Make game info more compact */
.info-row{
  display:flex;
  justify-content:space-between;
  gap:12px;
  padding:8px 0; /* Reduced from 10px */
  border-bottom:1px solid rgba(255,255,255,.06);
  font-size:13px; /* Reduced from 14px */
}
.info-row:last-child{border-bottom:none}
.verdict{
  margin-top:10px;
  padding:12px 12px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.03);
  font-size:14px;
}
.verdict .label{color:var(--muted);font-size:12px}
.verdict .value{font-size:16px;font-weight:700;margin-top:4px}

/* Modal */
.modal{
  display:none;position:fixed;z-index:1000;left:0;top:0;
  width:100%;height:100%;background-color:rgba(0,0,0,0.6);
}
.modal-content{
  background:rgba(15,26,47,0.95);
  margin:8% auto;
  padding:22px 22px;
  border:1px solid var(--line);
  border-radius:18px;
  width:92%;max-width:520px;
  color:var(--text);
  font-size:16px;
  text-align:left;
  position:relative;
}
.modal-content .close{
  position:absolute;top:12px;right:16px;
  font-size:22px;font-weight:bold;cursor:pointer;color:var(--muted);
}
.modal-content .close:hover{color:var(--primary)}

/* Quick specs form inside modal */
.qform{
  margin-top:12px;
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}
@media(max-width:520px){ .qform{grid-template-columns:1fr} }
.qform label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
.qform input,.qform select{
  width:100%;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(10,14,25,.55);
  color:var(--text);
}
.qactions{
  display:flex;
  gap:10px;
  justify-content:flex-end;
  margin-top:14px;
}
.qbtn{
  padding:10px 14px;
  border-radius:12px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.04);
  color:var(--text);
  cursor:pointer;
}
.qbtn.primary{
  border:none;
  background:linear-gradient(135deg,var(--primary),var(--accent));
  color:#fff;
  font-weight:700;
}

/* Make hero-cta-grid more compact */
.hero-cta-grid{
  margin-top:16px;
  display:grid;
  grid-template-columns:1.45fr .55fr;
  gap:16px;
}

.hero-cta-wrapper{
  border:1px solid var(--line);
  border-radius:16px;
  padding:14px 16px; /* Reduced from 16px */
  background:rgba(255,255,255,.04);
}

.hero-sidebar-card{
  border:1px solid var(--line);
  border-radius:16px;
  padding:14px 16px; /* Reduced from 16px */
  background:rgba(255,255,255,.04);
}

@media(max-width:900px){
  .hero-cta-grid{
    grid-template-columns:1fr;
  }
}

/* ===============================
   COMPATIBILITY REPORT STYLES
   =============================== */
.report-header{
  text-align:center;
  margin-bottom:24px;
  padding-bottom:20px;
  border-bottom:1px solid var(--line);
}
.report-header h2{
  font-size:24px;
  margin:0 0 8px 0;
}
.report-header .game-title{
  color:var(--muted);
  font-size:14px;
}

.verdict-banner{
  padding:20px;
  border-radius:14px;
  margin:20px 0;
  text-align:center;
  font-size:18px;
  font-weight:700;
  border:2px solid;
}
.verdict-banner.can-run{
  background:rgba(78,255,187,.12);
  border-color:var(--green);
  color:var(--green);
}
.verdict-banner.cannot-run{
  background:rgba(255,107,107,.12);
  border-color:var(--red);
  color:var(--red);
}
.verdict-banner.minimum{
  background:rgba(255,205,110,.12);
  border-color:var(--orange);
  color:var(--orange);
}

.compatibility-percentage{
  text-align:center;
  margin:24px 0;
}
.compat-circle{
  display:inline-block;
  width:120px;
  height:120px;
  border-radius:50%;
  background:conic-gradient(var(--primary) var(--percentage), rgba(255,255,255,.08) 0);
  position:relative;
}
.compat-circle::before{
  content:'';
  position:absolute;
  inset:8px;
  border-radius:50%;
  background:var(--card);
}
.compat-circle-text{
  position:absolute;
  inset:0;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  z-index:1;
}
.compat-circle-text .percent{
  font-size:32px;
  font-weight:700;
  color:var(--text);
}
.compat-circle-text .label{
  font-size:11px;
  color:var(--muted);
  margin-top:4px;
}

.component-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
  gap:16px;
  margin:24px 0;
}
.component-card{
  padding:16px;
  border-radius:14px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.03);
}
.component-card h3{
  font-size:14px;
  margin:0 0 12px 0;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:0.5px;
}
.component-status{
  display:flex;
  align-items:center;
  gap:8px;
  margin-bottom:16px;
}
.status-icon{
  width:24px;
  height:24px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:14px;
}
.status-icon.pass{
  background:var(--green);
  color:#000;
}
.status-icon.fail{
  background:var(--red);
  color:#fff;
}
.status-icon.warning{
  background:var(--orange);
  color:#000;
}
.status-text{
  font-size:16px;
  font-weight:600;
}
.status-text.pass{color:var(--green);}
.status-text.fail{color:var(--red);}
.status-text.warning{color:var(--orange);}

.score-comparison{
  display:flex;
  justify-content:space-between;
  gap:12px;
  margin-top:12px;
}
.score-item{
  text-align:center;
  flex:1;
}
.score-item .value{
  font-size:20px;
  font-weight:700;
  color:var(--text);
}
.score-item .label{
  font-size:11px;
  color:var(--muted);
  margin-top:4px;
}

.performance-bar{
  margin-top:12px;
  height:8px;
  background:rgba(255,255,255,.08);
  border-radius:999px;
  overflow:hidden;
  position:relative;
}
.performance-fill{
  height:100%;
  border-radius:999px;
  /* transition removed for performance */
}
.performance-fill.excellent{background:var(--green);}
.performance-fill.good{background:var(--primary);}
.performance-fill.fair{background:var(--orange);}
.performance-fill.poor{background:var(--red);}

.bottleneck-section{
  margin-top:24px;
  padding:16px;
  border-radius:14px;
  background:rgba(255,107,107,.08);
  border:1px solid rgba(255,107,107,.3);
}
.bottleneck-section h3{
  font-size:16px;
  margin:0 0 12px 0;
  color:var(--red);
}
.bottleneck-list{
  list-style:none;
  padding:0;
  margin:0;
}
.bottleneck-list li{
  padding:8px 0;
  font-size:14px;
  color:var(--text);
  border-bottom:1px solid rgba(255,255,255,.05);
}
.bottleneck-list li:last-child{
  border-bottom:none;
}

.report-footer{
  margin-top:32px;
  padding-top:20px;
  border-top:1px solid var(--line);
  text-align:center;
}
.report-footer .muted{
  font-size:13px;
  line-height:1.6;
}

@media(max-width:640px){
  .component-grid{
    grid-template-columns:1fr;
  }
  .score-comparison{
    flex-direction:column;
    gap:8px;
  }
}
</style>
@endpush

@section('content')
@php
  $activeHwId = session('active_hardware_id');

  $hasReq = !empty($reqByPlatform['pc'] ?? null)
        || !empty($reqByPlatform['mac'] ?? null)
        || !empty($reqByPlatform['linux'] ?? null);

  $price = $app->pricing->final_price ?? null;
  $hasPrice = !is_null($price);

  // Reviews %
  $reviewPercent = null;
  $reviewColor = null;
  if (!empty($app->reviews) && ($app->reviews->total_reviews ?? 0) > 0) {
    $reviewPercent = (($app->reviews->total_positive ?? 0) / max(1, $app->reviews->total_reviews)) * 100;
    $reviewColor = $reviewPercent >= 80 ? 'var(--green)' : ($reviewPercent >= 50 ? 'var(--orange)' : 'var(--red)');
  }

  // IMPORTANT: avoid "trying to read property on null" in blade
  $activeHw = $activeHardware ?? null;
@endphp

<div class="wrap">

  {{-- Top Bar --}}
  <div class="topbar">
    <a class="back" href="{{ route('apps.index') }}">← Back to Games</a>
    <div class="muted">App ID: <b>{{ $app->app_id }}</b></div>
  </div>

{{-- Hero --}}
<div class="hero">
  <img src="{{ $app->header_image ?: 'https://placehold.co/1200x600?text='.$app->name }}" alt="{{ $app->name }} header image">
  <div class="p">
    <h1>{{ $app->name }}</h1>

    <div class="meta">
      <span class="pill">{{ $app->type ?? 'Game' }}</span>
      @if($app->is_free)
        <span class="pill free">Free</span>
      @else
        <span class="pill paid">Paid</span>
      @endif
      @if($app->release_date)
        <span class="pill">Released {{ \Carbon\Carbon::parse($app->release_date)->toFormattedDateString() }}</span>
      @elseif($app->release_date_text)
        <span class="pill">{{ $app->release_date_text }}</span>
      @endif
      @if($hasReq)
        <span class="pill">System Requirements Available</span>
      @endif
    </div>
  </div>
</div>

{{-- NEW: Hero CTA Grid (side by side) --}}
<div class="hero-cta-grid">
  
  {{-- Left: CTA Buttons --}}
  <div class="hero-cta-wrapper">
    <div class="hero-cta">
      <button type="button" class="cta-btn big" onclick="checkCompatibility()">🖥️ Can I Run This?</button>
      @if($activeHwId)
        <button type="button" class="cta-secondary" onclick="openEditModal()">
          ⚙️ Edit My PC
        </button>
      @endif
    </div>
    <div class="muted" style="margin-top:10px">
      Check minimum & recommended requirements for Windows, macOS, and Linux and compare instantly.
    </div>
  </div>

  {{-- Right: Can I Run This Card --}}
  <div class="hero-sidebar-card">
    <h2>🖥️ Can I Run This?</h2>

    @if(!$activeHwId)
      <div class="muted">No PC profile selected.</div>
      <button class="cta-btn" style="margin-top:12px" onclick="checkCompatibility()">Check Now</button>
      <div class="muted" style="margin-top:10px">
        No saved PC yet — we'll ask for CPU/GPU/RAM in a quick popup.
      </div>
    @else
      <div class="muted">
        Using: <b>{{ $activeHw?->profile_name ?? 'My PC' }}</b>
      </div>

      <div class="verdict" id="verdictBox" style="display:none">
        <div class="label">Result</div>
        <div class="value" id="verdictText">—</div>
        <div class="muted" id="bottleneckText" style="margin-top:6px;display:none"></div>
      </div>

      <button class="cta-btn" style="margin-top:12px" onclick="checkCompatibility()">Check Now</button>
      <div class="muted" style="margin-top:10px">
        Fast check uses your saved profile and returns a clear verdict.
      </div>
      <a class="text-link" style="display:inline-block; margin-top:10px" href="{{ route('upgrade', ['game' => $app->slug]) }}">What would it take to run it well? →</a>
    @endif
  </div>

</div>

  {{-- Stats --}}
  <div class="stats">
    @if(!is_null($reviewPercent))
      <div class="stat-card">
        <h3>User Reviews</h3>
        <div class="stat-value">{{ $app->reviews->review_score_desc ?? 'Reviews' }}</div>
        <div class="progress-bar">
          <div class="progress-fill" style="width:{{ $reviewPercent }}%;background:{{ $reviewColor }}"></div>
        </div>
        <div class="muted" style="margin-top:8px">{{ number_format($app->reviews->total_reviews) }} reviews</div>
      </div>
    @endif

    @if($hasPrice)
      <div class="stat-card">
        <h3>Price</h3>
        <div class="stat-value">
          {{ $price == 0 ? 'Free' : '$'.number_format($price/100,2) }}
        </div>
        <div class="muted" style="margin-top:6px">
          @if(($app->pricing->discount_percent ?? 0) > 0)
            Discount: {{ $app->pricing->discount_percent }}%
          @else
            No discount
          @endif
        </div>
      </div>
    @endif

    <div class="stat-card">
      <h3>Popularity</h3>
      <div class="stat-value">{{ number_format($app->total_views ?? 0) }}</div>
      <div class="muted" style="margin-top:6px">Page views</div>
    </div>
  </div>

  {{-- Main Grid --}}
  <div class="grid">

    {{-- Left --}}
    <div>

      <div class="card">
        <h2>About {{ $app->name }}</h2>
        <div class="muted">
          {{ $app->short_description ?: 'No description available.' }}
        </div>
      </div>

      {{-- System Requirements (only shows platforms with data) --}}
<div class="card" style="margin-top:16px">
  <h2>{{ $app->name }} System Requirements</h2>
  <div class="muted">Official requirements (when provided). Some games don't list recommended specs.</div>

  <div class="req-tabs">
    <button class="req-tab active" data-mode="min" type="button">Minimum</button>
    <button class="req-tab" data-mode="rec" type="button">Recommended</button>
  </div>

  @foreach(['pc'=>'Windows','mac'=>'macOS','linux'=>'Linux'] as $k=>$label)
    @php
      $r = $reqByPlatform[$k] ?? null;
      // Check if platform has actual meaningful data (not just empty/null/dash values)
      $hasData = false;
      if ($r) {
        $fields = [
          $r->min_cpu, $r->min_ram, $r->min_gpu, $r->min_storage,
          $r->rec_cpu, $r->rec_ram, $r->rec_gpu, $r->rec_storage
        ];
        foreach ($fields as $field) {
          // Check if field has actual data (not null, not empty, not just dashes)
          if ($field && trim($field) && trim($field) !== '—' && trim($field) !== '-') {
            $hasData = true;
            break;
          }
        }
      }
    @endphp

    {{-- ✅ ONLY show if platform has actual data --}}
    @if($hasData)
      <div class="req">
        <b>💻 {{ $label }}</b>

        <div class="req-grid req-min">
          <div>CPU</div><div>{{ $r->min_cpu ?: '—' }}</div>
          <div>RAM</div><div>{{ $r->min_ram ?: '—' }}</div>
          <div>GPU</div><div>{{ $r->min_gpu ?: '—' }}</div>
          <div>Storage</div><div>{{ $r->min_storage ?: '—' }}</div>
          @if($r->min_directx)
            <div>DirectX</div><div>{{ $r->min_directx }}</div>
          @endif
        </div>

        <div class="req-grid req-rec" style="display:none">
          <div>CPU</div><div>{{ $r->rec_cpu ?: '—' }}</div>
          <div>RAM</div><div>{{ $r->rec_ram ?: '—' }}</div>
          <div>GPU</div><div>{{ $r->rec_gpu ?: '—' }}</div>
          <div>Storage</div><div>{{ $r->rec_storage ?: '—' }}</div>
          @if($r->rec_directx)
            <div>DirectX</div><div>{{ $r->rec_directx }}</div>
          @endif
        </div>
      </div>
    @endif
  @endforeach

  {{-- ✅ Show message if NO platforms have data --}}
  @if(empty($reqByPlatform['pc']) && empty($reqByPlatform['mac']) && empty($reqByPlatform['linux']))
    <div class="req">
      <div class="muted">No system requirements available for this game.</div>
    </div>
  @endif

  <div class="muted" style="margin-top:12px">
    Tip: If your GPU/CPU name is confusing, save your PC profile once and we'll reuse it across games.
  </div>
</div>

      {{-- Screenshots --}}
      <div class="card" style="margin-top:16px">
        <h2>Screenshots</h2>
        <div class="shots">
          @if(!empty($app->screenshots) && $app->screenshots->count() > 0)
            @foreach($app->screenshots->take(12) as $s)
              <img src="{{ $s->path_thumbnail }}" alt="{{ $app->name }} screenshot">
            @endforeach
          @else
            <div class="muted">No screenshots available.</div>
          @endif
        </div>
      </div>

      {{-- Trailer / Videos (HLS) --}}
      @if(!empty($app->movies) && $app->movies->count() > 0)
        @php
          $movie = ($app->movies->firstWhere('highlight', 1) ?? $app->movies->first());
          $hlsSrc = $movie->hls_h264;        // .m3u8
        @endphp

        <div class="card" style="margin-top:16px">
          <h2>Trailer</h2>

          <div class="muted" style="margin-bottom:10px">
            {{ $movie->name ?: ($app->name.' trailer') }}
          </div>

          @if($hlsSrc)
            <video
              id="trailerPlayer"
              controls
              playsinline
              preload="metadata"
              poster="{{ $movie->thumbnail ?: '' }}"
              style="width:100%; border-radius:14px; border:1px solid rgba(255,255,255,.10); background:rgba(0,0,0,.25);">
            </video>

            {{-- More videos --}}
            @if($app->movies->count() > 1)
              <div class="muted" style="margin-top:10px">More videos</div>

              <div class="shots" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
                @foreach($app->movies->take(8) as $m)
                  <button
                    type="button"
                    class="videoPick"
                    data-hls="{{ $m->hls_h264 }}"
                    style="padding:0;border:none;background:transparent;cursor:pointer;text-align:left">
                    <img src="{{ $m->thumbnail }}" alt="{{ $app->name }} video thumbnail" style="height:100px">
                    <div class="muted" style="margin-top:6px;font-size:12px">
                      {{ \Illuminate\Support\Str::limit($m->name ?? 'Trailer', 44) }}
                    </div>
                  </button>
                @endforeach
              </div>
            @endif

            {{-- HLS.js --}}
            <script src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
            <script>
              (function () {
                const video = document.getElementById('trailerPlayer');
                const firstHls = @json($hlsSrc);

                function playHls(url) {
                  if (!url || !video) return;

                  // Safari (and some iOS browsers) can play HLS natively
                  if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = url;
                    video.play().catch(() => {});
                    return;
                  }

                  // Other browsers use hls.js
                  if (window.Hls && Hls.isSupported()) {
                    if (window._hlsInstance) {
                      window._hlsInstance.destroy();
                    }
                    const hls = new Hls({ enableWorker: true });
                    window._hlsInstance = hls;
                    hls.loadSource(url);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, function () {
                      video.play().catch(() => {});
                    });
                  } else {
                    // Fallback: open link if browser can't play it
                    window.open(url, '_blank');
                  }
                }

                // autoplay first trailer
                playHls(firstHls);

                // click to switch trailers
                document.querySelectorAll('.videoPick').forEach(btn => {
                  btn.addEventListener('click', () => {
                    const url = btn.dataset.hls;
                    playHls(url);
                  });
                });
              })();
            </script>
          @else
            <div class="muted">No playable HLS link available for this game.</div>
          @endif
        </div>
      @endif

      {{-- SEO helper text --}}
      <div class="card" style="margin-top:16px">
        <h2>Can my PC run {{ $app->name }}?</h2>
        <div class="muted">
          Use the “Can I Run This?” button to compare your saved PC specs against the minimum and recommended requirements.
          You can also switch between Windows, macOS, and Linux requirements above if the game supports them.
        </div>
      </div>

    </div>

    {{-- Right Sidebar --}}
    <div>
      <div class="side-sticky">

        <div class="card" style="margin-top:16px">
          <h2>Game Info</h2>

          <div class="info-row">
            <span class="muted">Release Date</span>
            <span>
              {{ $app->release_date ? \Carbon\Carbon::parse($app->release_date)->toFormattedDateString() : ($app->release_date_text ?? 'Unknown') }}
            </span>
          </div>

          <div class="info-row">
            <span class="muted">Developers</span>
            <span>{{ is_array($app->developers) ? implode(', ', $app->developers) : ($app->developers ?: '—') }}</span>
          </div>

          <div class="info-row">
            <span class="muted">Publishers</span>
            <span>{{ is_array($app->publishers) ? implode(', ', $app->publishers) : ($app->publishers ?: '—') }}</span>
          </div>

          @if($app->has_metacritic_score)
            <div class="info-row">
              <span class="muted">Metacritic</span>
              <span>
                @if($app->metacritic_url)
                  <a href="{{ $app->metacritic_url }}" target="_blank" style="display:inline-flex;align-items:center;gap:4px">
                    <span style="padding:2px 8px;border-radius:4px;font-weight:600;background:{{ $app->metacritic_score >= 75 ? 'var(--green)' : ($app->metacritic_score >= 50 ? 'var(--orange)' : 'var(--red)') }};color:#000">
                      {{ $app->metacritic_score }}
                    </span>
                  </a>
                @else
                  <span style="padding:2px 8px;border-radius:4px;font-weight:600;background:{{ $app->metacritic_score >= 75 ? 'var(--green)' : ($app->metacritic_score >= 50 ? 'var(--orange)' : 'var(--red)') }};color:#000">
                    {{ $app->metacritic_score }}
                  </span>
                @endif
              </span>
            </div>
          @endif

          @if($app->has_achievements)
            <div class="info-row">
              <span class="muted">Achievements</span>
              <span>🏆 {{ $app->total_achievements }}</span>
            </div>
          @endif

          @if($app->has_controller_support)
            <div class="info-row">
              <span class="muted">Controller</span>
              <span style="text-transform:capitalize">
                🎮 {{ $app->controller_support }}
                @if($app->controller_support === 'full')
                  <span style="color:var(--green)">✓</span>
                @endif
              </span>
            </div>
          @endif

          @if(!empty($app->supported_platforms))
            <div class="info-row">
              <span class="muted">Platforms</span>
              <span style="display:flex;gap:4px;flex-wrap:wrap">
                @if($app->supports_windows)
                  <span style="padding:2px 8px;border-radius:4px;background:rgba(255,255,255,.08);font-size:11px">Windows</span>
                @endif
                @if($app->supports_mac)
                  <span style="padding:2px 8px;border-radius:4px;background:rgba(255,255,255,.08);font-size:11px">Mac</span>
                @endif
                @if($app->supports_linux)
                  <span style="padding:2px 8px;border-radius:4px;background:rgba(255,255,255,.08);font-size:11px">Linux</span>
                @endif
              </span>
            </div>
          @endif

          @if($app->has_dlc)
            <div class="info-row">
              <span class="muted">DLC Available</span>
              <span>{{ $app->dlc_count }} DLC{{ $app->dlc_count > 1 ? 's' : '' }}</span>
            </div>
          @endif

          <div class="info-row">
            <span class="muted">Required Age</span>
            <span>{{ $app->required_age_text ?: ($app->required_age ?? '—') }}</span>
          </div>

          <div class="info-row">
            <span class="muted">Requirements</span>
            <span>{{ $hasReq ? 'Available' : 'Missing' }}</span>
          </div>

          @if($hasPrice)
            <div class="info-row">
              <span class="muted">Price</span>
              <span>{{ $price == 0 ? 'Free' : '$'.number_format($price/100,2) }}</span>
            </div>
          @endif

          <div style="margin-top:10px">
            <a target="_blank" href="https://store.steampowered.com/app/{{ $app->app_id }}">
              View on Steam →
            </a>
            @if($app->website)
              <div style="margin-top:6px">
                <a target="_blank" href="{{ $app->website }}">Official Website →</a>
              </div>
            @endif
          </div>

        </div>

      </div>
    </div>

  </div>
</div>

{{-- Compatibility Report Modal (NEW - Detailed Report) --}}
<div id="compatReportModal" class="modal" aria-hidden="true">
  <div class="modal-content" style="max-width:900px; max-height:90vh; overflow-y:auto;">
    <span class="close" data-close="compatReportModal">&times;</span>

    <div id="compatReportContent">
      {{-- Report content will be injected here --}}
    </div>
  </div>
</div>

{{-- Simple Compatibility Result Modal (Fallback) --}}
<div id="compatModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <span class="close" data-close="compatModal">&times;</span>
    <p id="compatMessage" style="margin:0">Loading...</p>
  </div>
</div>

{{-- Quick Specs Modal (NEW) --}}
<div id="quickSpecsModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <span class="close" data-close="quickSpecsModal">&times;</span>

    <h2 style="margin:0 0 6px 0">Enter your PC specs</h2>
    <div class="muted">We’ll check compatibility for <b>{{ $app->name }}</b> only.</div>

    <form id="quickSpecsForm" class="qform">
      @csrf

      <div style="grid-column:1/-1">
        <label>CPU</label>
        <select id="qs_cpu_id" name="cpu_id" required>
          <option value="">Select your CPU...</option>
          @foreach($cpus as $manufacturer => $cpuList)
            <optgroup label="{{ strtoupper(str_replace('_', ' ', $manufacturer)) }}">
              @foreach($cpuList as $cpu)
                <option value="{{ $cpu->id }}">
                  {{ $cpu->name }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>

      <div style="grid-column:1/-1">
        <label>GPU</label>
        <select id="qs_gpu_id" name="gpu_id" required>
          <option value="">Select your GPU...</option>
          @foreach($gpus as $manufacturer => $gpuList)
            <optgroup label="{{ strtoupper(str_replace('_', ' ', $manufacturer)) }}">
              @foreach($gpuList as $gpu)
                <option value="{{ $gpu->id }}">
                  {{ $gpu->name }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>

      <div>
        <label>RAM</label>
        <select id="qs_ram_gb" name="ram_gb" required>
          <option value="">Select RAM</option>
          @foreach($ramOptions as $gb => $label)
            <option value="{{ $gb }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label>Platform</label>
        <select id="qs_platform" name="platform" required>
          <option value="">Select Platform</option>
          @foreach($platformOptions as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div style="grid-column:1/-1">
        <label>Save this PC for next time?</label>
        <select id="qs_save" name="save">
          <option value="0">No (just check this game)</option>
          <option value="1" selected>Yes (save as "My PC")</option>
        </select>
      </div>

      <div class="qactions" style="grid-column:1/-1">
        <button type="submit" class="qbtn primary">Check</button>
      </div>
    </form>

    <div class="muted" id="qs_error" style="margin-top:10px;display:none"></div>
  </div>
</div>

{{-- Edit PC Modal (SEPARATE) --}}
<div id="editPcModal" class="modal" aria-hidden="true">
  <div class="modal-content">
    <span class="close" data-close="editPcModal">&times;</span>

    <h2 style="margin:0 0 6px 0">Edit Your PC Specs</h2>
    <div class="muted">Update {{ $activeHw?->profile_name ?? 'My PC' }}</div>

    @if($activeHw && !$activeHw->cpu_id)
      <div class="muted" style="margin-top:12px;padding:10px;background:rgba(255,205,110,.1);border:1px solid rgba(255,205,110,.3);border-radius:8px;font-size:13px;">
        <strong>Note:</strong> Your PC profile uses the old format. Select your hardware from the dropdowns below to update it.
      </div>
    @endif

    <form id="editPcForm" class="qform" style="margin-top:16px">
      @csrf
      @method('PUT')

      <div style="grid-column:1/-1">
        <label>Profile Name</label>
        <input type="text" id="edit_profile_name" name="profile_name"
               value="{{ $activeHw?->profile_name ?? '' }}"
               placeholder="My Gaming PC" required>
      </div>

      <div style="grid-column:1/-1">
        <label>CPU</label>
        <select id="edit_cpu_id" name="cpu_id" required>
          <option value="">Select your CPU...</option>
          @foreach($cpus as $manufacturer => $cpuList)
            <optgroup label="{{ strtoupper(str_replace('_', ' ', $manufacturer)) }}">
              @foreach($cpuList as $cpu)
                <option value="{{ $cpu->id }}" {{ ($activeHw?->cpu_id ?? '') == $cpu->id ? 'selected' : '' }}>
                  {{ $cpu->name }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>

      <div style="grid-column:1/-1">
        <label>GPU</label>
        <select id="edit_gpu_id" name="gpu_id" required>
          <option value="">Select your GPU...</option>
          @foreach($gpus as $manufacturer => $gpuList)
            <optgroup label="{{ strtoupper(str_replace('_', ' ', $manufacturer)) }}">
              @foreach($gpuList as $gpu)
                <option value="{{ $gpu->id }}" {{ ($activeHw?->gpu_id ?? '') == $gpu->id ? 'selected' : '' }}>
                  {{ $gpu->name }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
      </div>

      <div>
        <label>RAM</label>
        <select id="edit_ram_gb" name="ram_gb" required>
          <option value="">Select RAM</option>
          @foreach($ramOptions as $gb => $label)
            <option value="{{ $gb }}" {{ ($activeHw?->ram_gb ?? '') == $gb ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label>Platform</label>
        <select id="edit_platform" name="platform" required>
          <option value="">Select Platform</option>
          @foreach($platformOptions as $value => $label)
            <option value="{{ $value }}" {{ ($activeHw?->platform ?? '') == $value ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="qactions" style="grid-column:1/-1">
        <button type="button" class="qbtn" onclick="closeModal('editPcModal')">Cancel</button>
        <button type="submit" class="qbtn primary">💾 Save Changes</button>
      </div>
    </form>

    <div class="muted" id="edit_error" style="margin-top:10px;display:none;color:var(--red)"></div>
  </div>
</div>

{{-- Similar Games Section --}}
@if($similarGames && $similarGames->isNotEmpty())
<div style="margin-top:32px;padding-top:32px;border-top:1px solid var(--line)">
  <h2 style="font-size:20px;margin-bottom:16px;font-weight:600">You Might Also Like</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px">
    @foreach($similarGames as $similar)
    <a href="{{ route('apps.show', $similar->slug) }}" style="display:block;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:rgba(255,255,255,.02);text-decoration:none"
       onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.3)'"
       onmouseout="this.style.transform='';this.style.boxShadow=''">

      @if($similar->header_image)
        <img src="{{ $similar->header_image }}"
             alt="{{ $similar->name }}"
             style="width:100%;height:100px;object-fit:cover"
             loading="lazy">
      @else
        <div style="width:100%;height:100px;background:linear-gradient(135deg,#1f4f8a 0%,#14706a 100%);display:flex;align-items:center;justify-content:center;color:white;font-size:12px;text-align:center;padding:8px">
          {{ $similar->name }}
        </div>
      @endif

      <div style="padding:12px">
        <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          {{ $similar->name }}
        </div>

        {{-- Genre badges --}}
        @if($similar->genres && $similar->genres->isNotEmpty())
        <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px">
          @foreach($similar->genres->take(2) as $genre)
          <span style="font-size:10px;padding:2px 6px;border-radius:4px;background:rgba(255,255,255,.08);color:var(--muted)">
            {{ $genre->name }}
          </span>
          @endforeach
        </div>
        @endif

        {{-- Price and reviews --}}
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
          @if($similar->pricing)
            @if($similar->pricing->discount_percent > 0)
              <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:11px;padding:2px 6px;border-radius:4px;background:var(--green);color:#000;font-weight:600">
                  -{{ $similar->pricing->discount_percent }}%
                </span>
                <span style="font-size:13px;font-weight:600;color:var(--green)">
                  ${{ number_format($similar->pricing->final_price / 100, 2) }}
                </span>
              </div>
            @else
              <span style="font-size:13px;font-weight:600;color:var(--text)">
                ${{ number_format($similar->pricing->final_price / 100, 2) }}
              </span>
            @endif
          @elseif($similar->is_free)
            <span style="font-size:13px;font-weight:600;color:var(--green)">Free</span>
          @else
            <span style="font-size:12px;color:var(--muted)">N/A</span>
          @endif

          @if($similar->reviews && $similar->reviews->total_reviews > 0)
            @php
              $percent = round(($similar->reviews->total_positive / $similar->reviews->total_reviews) * 100);
              $color = $percent >= 80 ? 'var(--green)' : ($percent >= 60 ? 'var(--orange)' : 'var(--red)');
            @endphp
            <span style="font-size:11px;color:{{ $color }}">
              {{ $percent }}%
            </span>
          @endif
        </div>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

<script>
(function () {
  /* =========================
     Helpers
     ========================= */
  function escapeHtml(str){
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function openModal(text){
    const modal = document.getElementById("compatModal");
    const message = document.getElementById("compatMessage");
    if (!modal || !message) return;
    message.textContent = text || '—';
    modal.style.display = "block";
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(id){
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = "none";
    modal.setAttribute('aria-hidden', 'true');
  }

  // IMPORTANT: inline onclick needs global function name
  window.closeModal = closeModal;

  function updateSidebarVerdict(verdict, bottlenecks){
    const box = document.getElementById('verdictBox');
    const txt = document.getElementById('verdictText');
    const btxt = document.getElementById('bottleneckText');
    if (!box || !txt) return;

    box.style.display = 'block';
    txt.textContent = verdict || '—';

    if (!btxt) return;

    if (typeof bottlenecks === 'string' && bottlenecks.trim().length > 0) {
      btxt.style.display = 'block';
      btxt.textContent = bottlenecks;
    } else if (Array.isArray(bottlenecks) && bottlenecks.length > 0) {
      btxt.style.display = 'block';
      btxt.textContent = 'Potential bottlenecks: ' + bottlenecks.join(', ');
    } else {
      btxt.style.display = 'none';
      btxt.textContent = '';
    }
  }

  /* =========================
     Modal Functions
     ========================= */
  function openQuickSpecsModal(){
    const modal = document.getElementById('quickSpecsModal');
    const err = document.getElementById('qs_error');
    if (err) { err.style.display = 'none'; err.textContent = ''; }
    if (!modal) return;

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');

    const platSel = document.getElementById('qs_platform');
    if (platSel && !platSel.value) platSel.value = 'windows_11';
  }

  /* =========================
     Edit Modal Functions
     ========================= */
  async function submitEditPc(event) {
    if (event) event.preventDefault();

    const profileName = document.getElementById('edit_profile_name')?.value?.trim();
    const cpu_id = document.getElementById('edit_cpu_id')?.value;
    const gpu_id = document.getElementById('edit_gpu_id')?.value;
    const ram_gb = document.getElementById('edit_ram_gb')?.value;
    const platform = document.getElementById('edit_platform')?.value;

    const errBox = document.getElementById('edit_error');
    if (!profileName || !cpu_id || !gpu_id || !ram_gb || !platform) {
      if (errBox) {
        errBox.style.display = 'block';
        errBox.textContent = 'Please fill all fields.';
      }
      return false;
    }

    const hardwareId = @json($activeHwId);
    if (!hardwareId) {
      if (errBox) {
        errBox.style.display = 'block';
        errBox.textContent = 'No hardware profile found.';
      }
      return false;
    }

    try {
      const r = await fetch(`/hardware/${hardwareId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': @json(csrf_token()),
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          profile_name: profileName,
          cpu_id: parseInt(cpu_id),
          gpu_id: parseInt(gpu_id),
          ram_gb: parseInt(ram_gb),
          platform
        })
      });

      const data = await r.json().catch(() => ({}));
      if (!r.ok) throw data;

      window.closeModal('editPcModal');

      location.reload();
    } catch (err) {
      console.error(err);
      if (errBox) {
        errBox.style.display = 'block';
        errBox.textContent = err?.message || err?.error || 'Failed to update. Please try again.';
      }
    }

    return false;
  }

  function openEditModal() {
    const activeHwId = @json($activeHwId);

    // If no saved profile yet, "Edit" should behave nicely
    if (!activeHwId) {
      openQuickSpecsModal();
      return;
    }

    const modal = document.getElementById('editPcModal');
    const err = document.getElementById('edit_error');
    if (err) { err.style.display = 'none'; err.textContent = ''; }
    if (!modal) return;

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
  }

  /* =========================
     Report Generation
     ========================= */
  function generateCompatibilityReport(data) {
    const gameName = @json($app->name);
    const details = data.details || {};
    const components = details.component_comparison || {};

    // Calculate overall compatibility percentage
    let compatPercent = 0;
    if (details.user_score && details.min_required) {
      compatPercent = Math.min(100, Math.round((details.user_score / details.min_required) * 100));
    }

    // Determine verdict class
    let verdictClass = 'minimum';
    if (data.can_run === 'yes_recommended') verdictClass = 'can-run';
    else if (data.can_run === 'no') verdictClass = 'cannot-run';

    // Build component cards
    const componentNames = { cpu: 'CPU', ram: 'RAM', gpu: 'Graphics Card' };
    let componentHTML = '';

    ['cpu', 'gpu', 'ram'].forEach(comp => {
      const c = components[comp] || {};
      const userScore = c.user || 0;
      const minScore = c.min || 0;
      const recScore = c.rec || 0;

      const passesMin = c.passes_min;
      const passesRec = c.passes_rec;

      let statusIcon = '✓';
      let statusClass = 'pass';
      let statusLabel = 'Meets Requirements';

      if (!passesMin) {
        statusIcon = '✗';
        statusClass = 'fail';
        statusLabel = 'Below Minimum';
      } else if (!passesRec) {
        statusIcon = '!';
        statusClass = 'warning';
        statusLabel = 'Minimum Only';
      }

      // Calculate performance percentage for bar
      const perfPercent = minScore > 0 ? Math.min(200, (userScore / minScore) * 100) : 100;
      let perfClass = 'poor';
      if (perfPercent >= 150) perfClass = 'excellent';
      else if (perfPercent >= 100) perfClass = 'good';
      else if (perfPercent >= 70) perfClass = 'fair';

      componentHTML += `
        <div class="component-card">
          <h3>${componentNames[comp]}</h3>
          <div class="component-status">
            <div class="status-icon ${statusClass}">${statusIcon}</div>
            <div class="status-text ${statusClass}">${statusLabel}</div>
          </div>
          <div class="score-comparison">
            <div class="score-item">
              <div class="value">${userScore}</div>
              <div class="label">Your Score</div>
            </div>
            <div class="score-item">
              <div class="value">${minScore}</div>
              <div class="label">Minimum</div>
            </div>
            <div class="score-item">
              <div class="value">${recScore}</div>
              <div class="label">Recommended</div>
            </div>
          </div>
          <div class="performance-bar">
            <div class="performance-fill ${perfClass}" style="width:${Math.min(100, perfPercent)}%"></div>
          </div>
        </div>
      `;
    });

    // Bottlenecks section
    let bottleneckHTML = '';
    if (data.bottlenecks && data.bottlenecks.length > 0) {
      bottleneckHTML = `
        <div class="bottleneck-section">
          <h3>⚠️ Performance Bottlenecks</h3>
          <ul class="bottleneck-list">
            ${data.bottlenecks.map(b => `<li>${b} is below minimum requirements</li>`).join('')}
          </ul>
        </div>
      `;
    }

    return `
      <div class="report-header">
        <h2>🎮 Compatibility Report</h2>
        <div class="game-title">${escapeHtml(gameName)}</div>
      </div>

      <div class="verdict-banner ${verdictClass}">
        ${escapeHtml(data.verdict || 'Unknown')}
      </div>

      <div class="compatibility-percentage">
        <div class="compat-circle" style="--percentage: ${compatPercent * 3.6}deg;">
          <div class="compat-circle-text">
            <div class="percent">${compatPercent}%</div>
            <div class="label">Compatible</div>
          </div>
        </div>
      </div>

      <div class="component-grid">
        ${componentHTML}
      </div>

      ${bottleneckHTML}

      <div class="report-footer">
        <div class="muted">
          This report is based on your saved PC profile. The scores represent normalized
          performance metrics where higher values indicate better performance.
          Results may vary based on game settings and system configuration.
        </div>
      </div>
    `;
  }

  function openCompatibilityReport(data) {
    const modal = document.getElementById('compatReportModal');
    const content = document.getElementById('compatReportContent');
    if (!modal || !content) return;

    content.innerHTML = generateCompatibilityReport(data);
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
  }

  /* =========================
     Main checkCompatibility()
     ========================= */
  async function checkCompatibility() {
    const hw = @json(session('active_hardware_id'));

    if (!hw) {
      openQuickSpecsModal();
      return;
    }

    openModal('Checking...');

    try {
      const r = await fetch(@json(route('hardware.check', $app->app_id)), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': @json(csrf_token()),
          'Accept': 'application/json'
        },
        body: JSON.stringify({ hardware_id: hw })
      });

      const data = await r.json().catch(() => ({}));
      if (!r.ok) throw data;

      const verdict = data.verdict || 'Result not available';
      updateSidebarVerdict(verdict, data.bottlenecks || null);

      // Close simple modal and show detailed report
      closeModal('compatModal');
      openCompatibilityReport(data);
    } catch (err) {
      console.error(err);
      openModal(err?.message || err?.error || 'Something went wrong. Please try again.');
    }
  }

  async function submitQuickSpecs(event){
    if (event) event.preventDefault();

    const cpu_id = document.getElementById('qs_cpu_id')?.value;
    const gpu_id = document.getElementById('qs_gpu_id')?.value;
    const ram_gb = document.getElementById('qs_ram_gb')?.value;
    const platform = document.getElementById('qs_platform')?.value;
    const save = Number(document.getElementById('qs_save')?.value || 0);

    const errBox = document.getElementById('qs_error');
    if (!cpu_id || !gpu_id || !ram_gb || !platform) {
      if (errBox) {
        errBox.style.display = 'block';
        errBox.textContent = 'Please fill CPU, GPU, RAM, and Platform.';
      }
      return false;
    }

    closeModal('quickSpecsModal');
    openModal('Checking...');

    try {
      const r = await fetch(@json(route('hardware.store')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': @json(csrf_token()),
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          profile_name: 'My PC',
          cpu_id: parseInt(cpu_id),
          gpu_id: parseInt(gpu_id),
          ram_gb: parseInt(ram_gb),
          platform
        })
      });

      const data = await r.json().catch(() => ({}));
      if (!r.ok) throw data;

      // After saving, check compatibility
      const checkResponse = await fetch(@json(route('hardware.check', $app->app_id)), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': @json(csrf_token()),
          'Accept': 'application/json'
        },
        body: JSON.stringify({ hardware_id: data.hardware_id || data.id })
      });

      const checkData = await checkResponse.json().catch(() => ({}));
      if (!checkResponse.ok) throw checkData;

      const verdict = checkData.verdict || 'Result not available';
      updateSidebarVerdict(verdict, checkData.bottlenecks || null);

      // Close simple modal and show detailed report
      closeModal('compatModal');
      openCompatibilityReport(checkData);

      // Reload if user chose to save
      if (save) {
        setTimeout(() => location.reload(), 3000);
      }
    } catch (err) {
      console.error(err);
      openModal(err?.message || err?.error || 'Something went wrong. Please try again.');
    }

    return false;
  }

  // IMPORTANT: inline onclick calls must exist on window
  window.checkCompatibility = checkCompatibility;
  window.openEditModal = openEditModal;

  /* =========================
     DOM bindings (only once)
     ========================= */
  document.addEventListener('DOMContentLoaded', () => {
    // requirements tabs
    document.querySelectorAll('.req-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.req-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const mode = btn.dataset.mode;
        document.querySelectorAll('.req-min').forEach(el => el.style.display = (mode === 'min' ? '' : 'none'));
        document.querySelectorAll('.req-rec').forEach(el => el.style.display = (mode === 'rec' ? '' : 'none'));
      });
    });

    // close buttons
    document.querySelectorAll('.close[data-close]').forEach(x => {
      x.addEventListener('click', () => closeModal(x.dataset.close));
    });

    // click outside to close modals
    window.addEventListener('click', (event) => {
      if (event.target.classList.contains('modal')) {
        const modalId = event.target.id;
        if (modalId) closeModal(modalId.replace('Modal', ''));
      }
    });

    const form = document.getElementById('quickSpecsForm');
    if (form) form.addEventListener('submit', submitQuickSpecs);

    const editForm = document.getElementById('editPcForm');
    if (editForm) editForm.addEventListener('submit', submitEditPc);
  });
})();
</script>
@endsection
