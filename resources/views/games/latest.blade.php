@extends('layouts.app')

@section('title', 'New PC Game Releases ' . date('Y') . ' – Latest Steam Games | GameSpecMatch')
@section('meta_description', 'Discover the latest PC games released on Steam. Check system requirements and see if your PC can run the newest games of ' . date('Y') . '. Updated daily.')
@section('canonical', route('apps.latest'))

@section('content')
<div class="wrap">
  <div class="page-header">
    <h1>🆕 Latest Releases</h1>
    <div style="color:var(--muted); font-size:14px">{{ number_format($apps->total()) }} recently released games</div>
  </div>

  {{-- Time Period Tabs --}}
  <div class="period-tabs">
    <a href="{{ route('apps.latest', array_merge(request()->except('period'), ['period' => 'week'])) }}" 
       class="period-tab {{ $period === 'week' ? 'active' : '' }}">
      This Week
    </a>
    <a href="{{ route('apps.latest', array_merge(request()->except('period'), ['period' => 'month'])) }}" 
       class="period-tab {{ $period === 'month' ? 'active' : '' }}">
      This Month
    </a>
    <a href="{{ route('apps.latest', array_merge(request()->except('period'), ['period' => '3months'])) }}" 
       class="period-tab {{ $period === '3months' ? 'active' : '' }}">
      Last 3 Months
    </a>
    <a href="{{ route('apps.latest', array_merge(request()->except('period'), ['period' => '6months'])) }}" 
       class="period-tab {{ $period === '6months' ? 'active' : '' }}">
      Last 6 Months
    </a>
    <a href="{{ route('apps.latest', array_merge(request()->except('period'), ['period' => 'year'])) }}" 
       class="period-tab {{ $period === 'year' ? 'active' : '' }}">
      This Year
    </a>
    <a href="{{ route('apps.latest', array_merge(request()->except('period'), ['period' => 'all'])) }}" 
       class="period-tab {{ $period === 'all' ? 'active' : '' }}">
      All Time
    </a>
  </div>

  <form method="GET" action="{{ route('apps.latest') }}" class="filters-bar">
    <input type="hidden" name="period" value="{{ $period }}">
    
    <div>
      <label>Genre</label>
      <select name="genre" onchange="this.form.submit()">
        <option value="">All Genres</option>
        @foreach($genres as $genre)
          <option value="{{ $genre->name }}" {{ request('genre') === $genre->name ? 'selected' : '' }}>
            {{ $genre->name }}
          </option>
        @endforeach
      </select>
    </div>

    @if(request('genre'))
      <a href="{{ route('apps.latest', ['period' => $period]) }}" style="padding:8px 16px; border:1px solid var(--line); border-radius:8px; color:var(--muted); text-decoration:none">
        Clear Filter
      </a>
    @endif
  </form>

  @if($apps->count() === 0)
    <div class="empty" style="padding:60px 20px; text-align:center; border:1px dashed var(--line); border-radius:16px">
      <div style="font-size:64px; margin-bottom:16px">🎮</div>
      <div style="font-size:18px; margin-bottom:8px">No games found</div>
      <div style="color:var(--muted)">Try a different time period</div>
    </div>
  @else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:16px">
      @foreach($apps as $app)
        <a class="game-card" href="{{ route('apps.show', $app) }}" style="display:flex; flex-direction:column; border:1px solid rgba(255,255,255,.08); border-radius:16px; overflow:hidden; background:rgba(255,255,255,.04); text-decoration:none; color:inherit; transition:all .2s">
          <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text='.urlencode($app->name) }}" alt="{{ $app->name }}" style="width:100%; height:140px; object-fit:cover">
          
          <div class="p" style="padding:14px; flex:1; display:flex; flex-direction:column">
            <div class="title" style="font-weight:600; margin-bottom:4px">{{ $app->name }}</div>
            <div class="release-date">Released {{ $app->release_date->diffForHumans() }}</div>
            
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:auto; padding-top:12px">
              @if($app->is_free)
                <span class="pill free">Free</span>
              @elseif($app->pricing)
                @if($app->pricing->discount_percent > 0)
                  <span class="pill discount">-{{ $app->pricing->discount_percent }}%</span>
                @endif
                <span class="pill paid">${{ number_format($app->pricing->final_price / 100, 2) }}</span>
              @endif

              @if($app->reviews && $app->reviews->total_reviews > 0)
                @php
                  $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                  $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
                @endphp
                <span class="pill {{ $reviewClass }}">{{ round($positivePercent) }}%</span>
              @endif
            </div>
          </div>
        </a>
      @endforeach
    </div>

    <div class="pager" style="margin-top:24px">
      {{ $apps->links('pagination.steam-dark') }}
    </div>
  @endif
</div>
@endsection