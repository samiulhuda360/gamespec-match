@extends('layouts.app')

@section('title', $sourceGame ? 'Games Similar to ' . $sourceGame->name . ' | GameSpecMatch' : 'Discover Similar Games | GameSpecMatch')

@section('meta_description', $sourceGame ? 'Find 32 games similar to ' . $sourceGame->name . '. Discover games with matching genres, gameplay, and style. Filter by price, platform, and sort by similarity.' : 'Find games similar to your favorites. Search any game and discover 32 similar titles with advanced filters.')

@push('styles')
<style>
.discover-hero{
  background:linear-gradient(135deg,rgba(110,168,255,.1) 0%,rgba(47,209,197,.1) 100%);
  border:1px solid var(--line);
  border-radius:18px;
  padding:32px 24px;
  margin-bottom:24px;
  text-align:center;
}
.discover-hero h1{font-size:28px;margin-bottom:12px;font-weight:700}
.discover-hero p{color:var(--muted);margin-bottom:24px}

.search-box{
  max-width:600px;
  margin:0 auto;
  position:relative;
}
.search-box input{
  width:100%;
  padding:14px 120px 14px 18px;
  border:1px solid var(--line);
  border-radius:12px;
  background:rgba(255,255,255,.04);
  color:var(--text);
  font-size:15px;
}
.search-box input:focus{
  outline:none;
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(110,168,255,.1);
}
.search-box button{
  position:absolute;
  right:6px;
  top:6px;
  padding:10px 24px;
  background:var(--primary);
  color:white;
  border:none;
  border-radius:8px;
  font-weight:600;
  cursor:pointer;
  transition:all .2s;
}
.search-box button:hover{background:var(--primary-dark)}
.search-box .suggestbox{
  position:absolute;
  top:calc(100% + 8px);
  left:0;
  right:0;
  z-index:100;
}

.source-game{
  background:rgba(255,255,255,.02);
  border:1px solid var(--line);
  border-radius:14px;
  padding:16px;
  margin-bottom:24px;
  display:flex;
  align-items:center;
  gap:16px;
}
.source-game img{width:120px;height:60px;object-fit:cover;border-radius:8px}
.source-game-info h2{font-size:18px;margin-bottom:4px}
.source-game-info .genres{display:flex;gap:6px;flex-wrap:wrap}
.source-game-info .genres span{
  font-size:11px;
  padding:3px 8px;
  border-radius:6px;
  background:rgba(255,255,255,.08);
  color:var(--muted);
}

.filters{
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  margin-bottom:20px;
  align-items:center;
}
.filters select{
  padding:10px 14px;
  border:1px solid var(--line);
  border-radius:8px;
  background:rgba(255,255,255,.04);
  color:var(--text);
  font-size:14px;
  cursor:pointer;
}
.filters select:focus{outline:none;border-color:var(--primary)}

.results-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
  padding-bottom:16px;
  border-bottom:1px solid var(--line);
}
.results-header h2{font-size:20px;font-weight:600}
.results-count{color:var(--muted);font-size:14px}

.games-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
  gap:20px;
  margin-bottom:32px;
}
.game-card{
  border:1px solid var(--line);
  border-radius:14px;
  overflow:hidden;
  background:rgba(255,255,255,.02);
  transition:all .2s;
  text-decoration:none;
  display:block;
}
.game-card:hover{
  transform:translateY(-4px);
  box-shadow:0 12px 32px rgba(0,0,0,.4);
  border-color:var(--primary);
}
.game-card img{width:100%;height:110px;object-fit:cover}
.game-card-body{padding:14px}
.game-card-title{
  font-size:14px;
  font-weight:600;
  color:var(--text);
  margin-bottom:8px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.game-card-genres{
  display:flex;
  gap:4px;
  flex-wrap:wrap;
  margin-bottom:10px;
}
.game-card-genres span{
  font-size:10px;
  padding:2px 6px;
  border-radius:4px;
  background:rgba(255,255,255,.06);
  color:var(--muted);
}
.game-card-footer{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:8px;
}
.game-price{font-size:14px;font-weight:600;color:var(--text)}
.game-price.free{color:var(--green)}
.game-price.discount{color:var(--green)}
.game-review{font-size:12px;padding:4px 8px;border-radius:6px}
.game-review.positive{background:rgba(78,255,187,.15);color:var(--green)}
.game-review.mixed{background:rgba(255,205,110,.15);color:var(--orange)}
.game-review.negative{background:rgba(255,107,107,.15);color:var(--red)}

.no-results{
  text-align:center;
  padding:64px 20px;
  color:var(--muted);
}
.no-results h3{font-size:20px;margin-bottom:12px}

@media(max-width:768px){
  .discover-hero{padding:24px 16px}
  .discover-hero h1{font-size:22px}
  .games-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
  .filters{flex-direction:column;align-items:stretch}
  .filters select{width:100%}
}
</style>
@endpush

@section('content')
{{-- Hero Section --}}
<div class="discover-hero">
  <h1>🔍 Discover Similar Games</h1>
  <p>Search for any game and find 32 similar titles based on genres, gameplay, and style</p>

  <form action="{{ route('discover.similar') }}" method="GET" class="search-box">
    <input
      type="text"
      id="gameSearch"
      name="game"
      placeholder="Search for a game (e.g., Cyberpunk 2077, Skyrim, GTA V...)"
      value="{{ $searchQuery }}"
      autocomplete="off"
      required
      autofocus
    >
    <div class="suggestbox" id="suggestBox"></div>
    <button type="submit">Find Similar</button>
  </form>
</div>

@if($sourceGame)
  {{-- Source Game Display --}}
  <div class="source-game">
    @if($sourceGame->header_image)
      <img src="{{ $sourceGame->header_image }}" alt="{{ $sourceGame->name }}">
    @else
      <div style="width:120px;height:60px;background:linear-gradient(135deg,#1f4f8a 0%,#14706a 100%);border-radius:8px"></div>
    @endif

    <div class="source-game-info" style="flex:1">
      <h2>{{ $sourceGame->name }}</h2>
      @if($sourceGame->genres && $sourceGame->genres->isNotEmpty())
      <div class="genres">
        @foreach($sourceGame->genres as $genre)
          <span>{{ $genre->name }}</span>
        @endforeach
      </div>
      @endif
    </div>

    @if($sourceGame->pricing)
      <div style="text-align:right">
        <div style="font-size:14px;font-weight:600;color:var(--primary)">
          ${{ number_format($sourceGame->pricing->final_price / 100, 2) }}
        </div>
      </div>
    @endif
  </div>

  {{-- Filters --}}
  <form action="{{ route('discover.similar') }}" method="GET" class="filters">
    <input type="hidden" name="game" value="{{ $searchQuery }}">

    <select name="genre" onchange="this.form.submit()">
      <option value="">All Genres</option>
      @foreach($genres as $genre)
        <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
          {{ $genre->name }}
        </option>
      @endforeach
    </select>

    <select name="platform" onchange="this.form.submit()">
      <option value="">All Platforms</option>
      <option value="pc" {{ request('platform') == 'pc' ? 'selected' : '' }}>Windows</option>
      <option value="mac" {{ request('platform') == 'mac' ? 'selected' : '' }}>macOS</option>
      <option value="linux" {{ request('platform') == 'linux' ? 'selected' : '' }}>Linux</option>
    </select>

    <select name="price_max" onchange="this.form.submit()">
      <option value="">Any Price</option>
      <option value="10" {{ request('price_max') == '10' ? 'selected' : '' }}>Under $10</option>
      <option value="20" {{ request('price_max') == '20' ? 'selected' : '' }}>Under $20</option>
      <option value="30" {{ request('price_max') == '30' ? 'selected' : '' }}>Under $30</option>
      <option value="50" {{ request('price_max') == '50' ? 'selected' : '' }}>Under $50</option>
    </select>

    <select name="sort" onchange="this.form.submit()">
      <option value="similarity" {{ request('sort') == 'similarity' ? 'selected' : '' }}>Most Similar</option>
      <option value="popularity" {{ request('sort') == 'popularity' ? 'selected' : '' }}>Most Popular</option>
      <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
      <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
      <option value="release_date" {{ request('sort') == 'release_date' ? 'selected' : '' }}>Latest Releases</option>
    </select>
  </form>

  {{-- Results Header --}}
  <div class="results-header">
    <h2>Similar Games</h2>
    <div class="results-count">Found {{ $similarGames->count() }} games</div>
  </div>

  {{-- Games Grid --}}
  @if($similarGames->isNotEmpty())
    <div class="games-grid">
      @foreach($similarGames as $game)
        <a href="{{ route('apps.show', $game->slug) }}" class="game-card">
          @if($game->header_image)
            <img src="{{ $game->header_image }}" alt="{{ $game->name }}" loading="lazy">
          @else
            <div style="width:100%;height:110px;background:linear-gradient(135deg,#1f4f8a 0%,#14706a 100%);display:flex;align-items:center;justify-content:center;color:white;font-size:11px;padding:8px;text-align:center">
              {{ $game->name }}
            </div>
          @endif

          <div class="game-card-body">
            <div class="game-card-title">{{ $game->name }}</div>

            @if($game->genres && $game->genres->isNotEmpty())
              <div class="game-card-genres">
                @foreach($game->genres->take(3) as $genre)
                  <span>{{ $genre->name }}</span>
                @endforeach
              </div>
            @endif

            <div class="game-card-footer">
              <div class="game-price {{ $game->is_free ? 'free' : ($game->pricing && $game->pricing->discount_percent > 0 ? 'discount' : '') }}">
                @if($game->is_free)
                  Free
                @elseif($game->pricing)
                  ${{ number_format($game->pricing->final_price / 100, 2) }}
                @else
                  N/A
                @endif
              </div>

              @if($game->reviews && $game->reviews->total_reviews > 0)
                @php
                  $percent = round(($game->reviews->total_positive / $game->reviews->total_reviews) * 100);
                  $reviewClass = $percent >= 70 ? 'positive' : ($percent >= 40 ? 'mixed' : 'negative');
                @endphp
                <div class="game-review {{ $reviewClass }}">
                  {{ $percent }}%
                </div>
              @endif
            </div>
          </div>
        </a>
      @endforeach
    </div>
  @else
    <div class="no-results">
      <h3>No similar games found</h3>
      <p>Try adjusting your filters or search for a different game</p>
    </div>
  @endif
@endif

@endsection

@push('scripts')
<script>
// Autocomplete functionality
(function() {
  const input = document.getElementById('gameSearch');
  const suggestBox = document.getElementById('suggestBox');
  const form = input.closest('form');
  let debounceTimer;

  input.addEventListener('input', function(e) {
    const val = e.target.value.trim();

    clearTimeout(debounceTimer);

    if (val.length < 2) {
      suggestBox.style.display = 'none';
      return;
    }

    debounceTimer = setTimeout(async () => {
      try {
        const res = await fetch(`{{ route('apps.suggest') }}?q=${encodeURIComponent(val)}`);
        const items = await res.json();

        if (items.length === 0) {
          suggestBox.style.display = 'none';
          return;
        }

        suggestBox.innerHTML = items.map(item => `
          <a href="#" class="suggest-item" data-name="${item.name}">
            <span class="suggest-name">${item.name}</span>
            <span class="badge">${item.type === 'game' ? 'Game' : 'App'}</span>
          </a>
        `).join('');

        suggestBox.style.display = 'block';
      } catch (err) {
        console.error('Autocomplete error:', err);
      }
    }, 300);
  });

  // Handle click on suggestion
  suggestBox.addEventListener('click', function(e) {
    e.preventDefault();
    const item = e.target.closest('.suggest-item');
    if (item) {
      const name = item.getAttribute('data-name');
      input.value = name;
      suggestBox.style.display = 'none';
      form.submit();
    }
  });

  // Hide suggestions when clicking outside
  document.addEventListener('click', function(e) {
    if (!input.contains(e.target) && !suggestBox.contains(e.target)) {
      suggestBox.style.display = 'none';
    }
  });

  // Hide suggestions on ESC key
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      suggestBox.style.display = 'none';
    }
  });
})();
</script>
@endpush
