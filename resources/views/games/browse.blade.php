@extends('layouts.app')

@push('styles')
<style>
.page-header{
  text-align:center;
  padding:40px 20px;
  background:linear-gradient(135deg, rgba(110,168,255,.15), rgba(47,209,197,.15));
  border:1px solid rgba(110,168,255,.3);
  border-radius:18px;
  margin-bottom:20px;
}
.page-header h1{
  margin:0 0 8px 0;
  font-size:36px;
  background:linear-gradient(135deg, var(--primary), var(--accent));
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}
.filters-bar{
  display:flex;
  gap:12px;
  align-items:center;
  flex-wrap:wrap;
  margin-bottom:20px;
  padding:16px;
  background:rgba(255,255,255,.04);
  border:1px solid var(--line);
  border-radius:14px;
}
.filters-bar label{
  font-size:13px;
  color:var(--muted);
  margin-right:6px;
}
.filters-bar select, .filters-bar input{
  padding:8px 12px;
  border-radius:8px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  color:var(--text);
  font-size:13px;
}
.filter-group{
  display:flex;
  align-items:center;
  gap:8px;
}
</style>
@endpush

@section('content')
<div class="wrap">
  <div class="page-header">
    <h1>🎮 Browse All Games</h1>
    <div style="color:var(--muted); font-size:14px">Discover from {{ number_format($apps->total()) }} Steam games</div>
  </div>

  <form method="GET" action="{{ route('apps.browse') }}" class="filters-bar">
    <div class="filter-group">
      <input type="text" name="q" placeholder="🔍 Search..." value="{{ request('q') }}" style="min-width:200px">
    </div>

    <div class="filter-group">
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

    <div class="filter-group">
      <label>
        <input type="checkbox" name="free" value="1" {{ request('free') ? 'checked' : '' }} onchange="this.form.submit()">
        Free Only
      </label>
    </div>

    <div class="filter-group">
      <label>Min $</label>
      <input type="number" name="price_min" placeholder="0" min="0" value="{{ request('price_min') }}" style="width:80px">
    </div>

    <div class="filter-group">
      <label>Max $</label>
      <input type="number" name="price_max" placeholder="Any" min="0" value="{{ request('price_max') }}" style="width:80px">
    </div>

    <div class="filter-group">
      <label>Sort</label>
      <select name="sort" onchange="this.form.submit()">
        <option value="recommendations" {{ request('sort', 'recommendations') === 'recommendations' ? 'selected' : '' }}>Most Recommended</option>
        <option value="reviews" {{ request('sort') === 'reviews' ? 'selected' : '' }}>Most Reviewed</option>
        <option value="date" {{ request('sort') === 'date' ? 'selected' : '' }}>Newest</option>
        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Lowest Price</option>
        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>A-Z</option>
      </select>
    </div>

    <button type="submit" style="padding:8px 16px; border:none; border-radius:8px; background:var(--primary); color:white; cursor:pointer">
      Apply
    </button>
    
    @if(request()->hasAny(['q', 'genre', 'free', 'price_min', 'price_max', 'sort']))
      <a href="{{ route('apps.browse') }}" style="padding:8px 16px; border:1px solid var(--line); border-radius:8px; color:var(--muted); text-decoration:none">
        Clear
      </a>
    @endif
  </form>

  @if($apps->count() === 0)
    <div class="empty" style="padding:60px 20px; text-align:center; border:1px dashed var(--line); border-radius:16px">
      <div style="font-size:64px; margin-bottom:16px">🎮</div>
      <div style="font-size:18px; margin-bottom:8px">No games found</div>
      <div style="color:var(--muted)">Try adjusting your filters</div>
    </div>
  @else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:16px">
      @foreach($apps as $app)
        <a class="game-card" href="{{ route('apps.show', $app) }}" style="display:flex; flex-direction:column; border:1px solid rgba(255,255,255,.08); border-radius:16px; overflow:hidden; background:rgba(255,255,255,.04); text-decoration:none; color:inherit; transition:all .2s">
          <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text='.urlencode($app->name) }}" alt="{{ $app->name }}" style="width:100%; height:140px; object-fit:cover">
          
          <div class="p" style="padding:14px; flex:1; display:flex; flex-direction:column">
            <div class="title" style="font-weight:600; margin-bottom:8px">{{ $app->name }}</div>
            
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:auto">
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