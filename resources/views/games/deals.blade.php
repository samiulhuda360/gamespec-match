{{-- File: resources/views/deals/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
.deals-header{
  text-align:center;
  padding:40px 20px;
  background:linear-gradient(135deg, rgba(255,107,107,.15), rgba(255,205,110,.15));
  border:1px solid rgba(255,205,110,.3);
  border-radius:18px;
  margin-bottom:20px;
}
.deals-header h1{
  margin:0 0 8px 0;
  font-size:36px;
  background:linear-gradient(135deg, #ff6b6b, #ffcd6e);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}
.deals-filters{
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
.deals-filters label{
  font-size:13px;
  color:var(--muted);
  margin-right:6px;
}
.deals-filters select, .deals-filters input{
  padding:8px 12px;
  border-radius:8px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  color:var(--text);
  font-size:13px;
}

.discount-badge{
  position:absolute;
  top:10px;
  right:10px;
  background:linear-gradient(135deg, #ff6b6b, #ff8e53);
  color:white;
  font-weight:700;
  font-size:18px;
  padding:8px 14px;
  border-radius:10px;
  box-shadow:0 4px 12px rgba(255,107,107,.4);
  z-index:10;
}

.savings{
  font-size:13px;
  color:var(--green);
  font-weight:600;
  margin-top:6px;
}
.original-price{
  text-decoration:line-through;
  color:var(--muted);
  font-size:13px;
  margin-right:8px;
}
.final-price{
  font-size:20px;
  font-weight:700;
  color:var(--green);
}
.deal-meta{
  display:flex;
  gap:10px;
  margin-top:8px;
  flex-wrap:wrap;
}
</style>
@endpush

@section('content')
<div class="wrap">
  <div class="deals-header">
    <h1>🔥 Hot Deals & Discounts</h1>
    <div style="color:var(--muted); font-size:14px">Save big on {{ $apps->total() }} games on sale</div>
  </div>

  <form method="GET" action="{{ route('deals.index') }}" class="deals-filters">
    <div>
      <label>Minimum Discount</label>
      <select name="min_discount" onchange="this.form.submit()">
        <option value="10" {{ request('min_discount', 10) == 10 ? 'selected' : '' }}>10% or more</option>
        <option value="25" {{ request('min_discount') == 25 ? 'selected' : '' }}>25% or more</option>
        <option value="50" {{ request('min_discount') == 50 ? 'selected' : '' }}>50% or more</option>
        <option value="75" {{ request('min_discount') == 75 ? 'selected' : '' }}>75% or more</option>
      </select>
    </div>

    <div>
      <label>Max Price</label>
      <input type="number" name="max_price" placeholder="Any" min="0" value="{{ request('max_price') }}" style="width:100px">
    </div>

    <div>
      <label>Genre</label>
      <select name="genre" onchange="this.form.submit()">
        <option value="">All Genres</option>
        @foreach($genres as $genre)
          <option value="{{ $genre->name }}" {{ request('genre') === $genre->name ? 'selected' : '' }}>{{ $genre->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label>Sort By</label>
      <select name="sort" onchange="this.form.submit()">
        <option value="discount_desc" {{ request('sort', 'discount_desc') === 'discount_desc' ? 'selected' : '' }}>Biggest Discount</option>
        <option value="savings_desc" {{ request('sort') === 'savings_desc' ? 'selected' : '' }}>Most Money Saved</option>
        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Lowest Price</option>
        <option value="recommendations_desc" {{ request('sort') === 'recommendations_desc' ? 'selected' : '' }}>Most Recommended</option>
      </select>
    </div>

    <button type="submit" style="padding:8px 16px; border:none; border-radius:8px; background:var(--brand); color:white; cursor:pointer">
      Apply
    </button>
    
    @if(request()->hasAny(['min_discount', 'max_price', 'genre', 'sort']))
      <a href="{{ route('deals.index') }}" style="padding:8px 16px; border:1px solid var(--line); border-radius:8px; color:var(--muted); text-decoration:none">
        Clear
      </a>
    @endif
  </form>

  @if($apps->count() === 0)
    <div class="empty" style="padding:60px 20px; text-align:center; border:1px dashed var(--line); border-radius:16px">
      <div style="font-size:64px; margin-bottom:16px">🎮</div>
      <div style="font-size:18px; margin-bottom:8px">No deals found</div>
      <div style="color:var(--muted)">Try adjusting your filters or check back later</div>
    </div>
  @else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:16px">
      @foreach($apps as $app)
        <a class="game-card" href="{{ route('apps.show', $app->slug) }}">
          @if($app->pricing && $app->pricing->discount_percent > 0)
            <div class="discount-badge">-{{ $app->pricing->discount_percent }}%</div>
          @endif
          
          <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300?text=No+Image' }}" alt="" style="height:140px">
          
          <div class="p" style="padding:14px">
            <div class="title">{{ $app->name }}</div>
            
            @if($app->pricing)
              <div style="margin-top:12px">
                <div>
                  <span class="original-price">${{ number_format($app->pricing->initial_price / 100, 2) }}</span>
                  <span class="final-price">${{ number_format($app->pricing->final_price / 100, 2) }}</span>
                </div>
                <div class="savings">
                  💰 Save ${{ number_format(($app->pricing->initial_price - $app->pricing->final_price) / 100, 2) }}
                </div>
              </div>
            @endif

            <div class="deal-meta">
              @if($app->reviews && $app->reviews->total_reviews > 0)
                @php
                  $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                  $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
                @endphp
                <span class="pill {{ $reviewClass }}">
                  {{ $app->reviews->review_score_desc }}
                </span>
              @endif
              
              @if($app->genres->isNotEmpty())
                <span class="pill">{{ $app->genres->first()->name }}</span>
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