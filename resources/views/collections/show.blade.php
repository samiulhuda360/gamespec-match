{{-- resources/views/collections/show.blade.php --}}
@extends('layouts.app')

@php
  $pageTitle = $collection->seo_title ?: $collection->title;
  $pageDesc  = $collection->seo_description ?: ($collection->subtitle ?: 'Browse games in this collection.');
  $pageUrl   = url('/collections/' . $collection->slug);
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDesc)
@section('canonical', $pageUrl)

@section('og_title', $pageTitle)
@section('og_description', $pageDesc)
@section('og_url', $pageUrl)
@section('og_image', $collection->hero_image ?: asset('images/og-default.png'))

@section('twitter_title', $pageTitle)
@section('twitter_description', $pageDesc)
@section('twitter_image', $collection->hero_image ?: asset('images/og-default.png'))

@php
  $jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'description' => $pageDesc,
    'url' => $pageUrl,
  ];
@endphp

@push('jsonld')
<script type="application/ld+json">
{!! json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush


@push('styles')
<style>
  /* small page-only tweaks, keeping your global design */
  .collection-hero{
    padding:38px 20px;
    border-radius:18px;
    border:1px solid var(--line);
    background:linear-gradient(135deg, rgba(110,168,255,.10), rgba(47,209,197,.07));
    margin-bottom:22px;
    text-align:left;
  }
  .collection-hero h1{font-size:34px; font-weight:900; margin-bottom:8px}
  .collection-hero .sub-text{color:var(--muted); font-size:14px; margin-bottom:14px}
  .collection-hero .intro{color:rgba(255,255,255,.82); line-height:1.7; max-width:900px}
  .collection-bar{
    display:flex; justify-content:space-between; align-items:center;
    gap:12px; flex-wrap:wrap;
    margin:18px 0 16px;
  }
  .collection-bar .count{color:var(--muted); font-size:13px}
  .price-row{display:flex; justify-content:space-between; align-items:center; gap:10px}
  .price{font-weight:800}
  .strike{color:var(--muted); text-decoration:line-through; margin-right:8px; font-weight:500; font-size:12px}
</style>
@endpush

@section('content')
<div class="wrap">

  <div class="collection-hero">
    <h1>{{ $collection->title }}</h1>

    @if($collection->subtitle)
      <div class="sub-text">{{ $collection->subtitle }}</div>
    @endif

    <div class="meta" style="margin-bottom:14px;">
      <span class="badge">Type: {{ $collection->type }}</span>
      @if($collection->type === 'year' && $collection->year)
        <span class="badge">Year: {{ $collection->year }}</span>
      @endif
      <span class="badge">Updated automatically</span>
    </div>

    @if($collection->intro)
      <div class="intro">{{ $collection->intro }}</div>
    @endif
  </div>

  <div class="collection-bar">
    <div class="count">
      Showing {{ $games->count() }} games (page {{ $games->currentPage() }} of {{ $games->lastPage() }})
    </div>
    <a class="nav-btn" href="{{ route('apps.index') }}">← Back</a>
  </div>

  @if($games->count())
    <div class="grid">
      @foreach($games as $g)
        @php
          $discount = (int)($g->discount_percent ?? 0);
          $isNew = $g->release_date && \Illuminate\Support\Carbon::parse($g->release_date)->gte(now()->subDays(7));

          $initial = is_null($g->initial_price) ? null : ($g->initial_price / 100);
          $final   = is_null($g->final_price) ? null : ($g->final_price / 100);
        @endphp

        <a class="card" href="{{ url('/game/'.$g->app_id) }}">
          @if($g->header_image)
            <img class="thumb" src="{{ $g->header_image }}" alt="{{ $g->name }}">
          @else
            <div class="thumb"></div>
          @endif

          <div class="card-content">
            <div class="title">{{ $g->name }}</div>

            <div class="meta">
              @if($g->release_date)
                <span>{{ $g->release_date }}</span><span class="meta-dot"></span>
              @endif
              <span>{{ number_format((int)$g->total_reviews) }} reviews</span>
              @if(!empty($g->total_recommendations))
                <span class="meta-dot"></span><span>{{ number_format((int)$g->total_recommendations) }} recs</span>
              @endif
            </div>

            <div class="badges">
              @if($g->is_free)
                <span class="pill free">FREE</span>
              @else
                <span class="pill paid">PAID</span>
              @endif

              @if($isNew)
                <span class="pill positive">NEW</span>
              @endif

              @if($discount >= 40)
                <span class="pill discount">-{{ $discount }}%</span>
              @endif
            </div>

            <div class="price-row">
              <div class="meta">Price</div>
              <div class="price">
                @if($g->is_free)
                  FREE
                @elseif(!is_null($final))
                  @if(!is_null($initial) && $final < $initial)
                    <span class="strike">${{ number_format($initial, 2) }}</span>
                  @endif
                  ${{ number_format($final, 2) }}
                @else
                  <span class="meta">—</span>
                @endif
              </div>
            </div>

          </div>
        </a>
      @endforeach
    </div>
  @else
    <div class="empty">
      <div class="emoji">🫥</div>
      <h2 style="margin-bottom:10px;">No games found</h2>
      <p class="sub-text">Try adjusting the collection logic (week/year/latest) or data availability.</p>
    </div>
  @endif

  {{-- Pagination styled with your CSS --}}
@if($games->hasPages())
  @php
    // UrlWindow returns an array: ['first' => [], 'slider' => [], 'last' => []]
    $window = \Illuminate\Pagination\UrlWindow::make($games);

    // Convert that into the same "elements" structure Laravel uses in its default pagination views
    $groups = array_values(array_filter([
      $window['first'] ?? null,
      $window['slider'] ?? null,
      $window['last'] ?? null,
    ]));

    $elements = [];
    foreach ($groups as $idx => $group) {
      if ($idx > 0) $elements[] = '...';
      $elements[] = $group;
    }
  @endphp

  <div class="pager">
    <div class="steam-pager">
      <a class="p {{ $games->onFirstPage() ? 'disabled' : '' }}"
         href="{{ $games->previousPageUrl() ?: '#' }}">Prev</a>

      @foreach($elements as $element)
        @if(is_string($element))
          <span class="p disabled">{{ $element }}</span>
        @elseif(is_array($element))
          @foreach($element as $page => $url)
            <a class="p {{ $page == $games->currentPage() ? 'active' : '' }}"
               href="{{ $url }}">{{ $page }}</a>
          @endforeach
        @endif
      @endforeach

      <a class="p {{ $games->hasMorePages() ? '' : 'disabled' }}"
         href="{{ $games->nextPageUrl() ?: '#' }}">Next</a>
    </div>
  </div>
@endif


</div>
@endsection
