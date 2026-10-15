@extends('layouts.app')

@section('title', 'Advanced Game Search – Find PC Games by Specs, Genre & Price | GameSpecMatch')
@section('meta_description', 'Advanced search for Steam games. Filter by genre, developer, price, release date, system requirements, and more. Find exactly what you\'re looking for.')
@section('canonical', route('search.advanced'))

@push('styles')
<style>
.search-hero{
  text-align:center;
  padding:30px 20px;
  margin-bottom:24px;
}
.search-hero h1{
  margin:0 0 8px 0;
  font-size:32px;
  background:linear-gradient(135deg, var(--brand), var(--brand2));
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}
.filter-container{
  background:rgba(255,255,255,.04);
  border:1px solid var(--line);
  border-radius:14px;
  padding:20px;
  margin-bottom:20px;
}

.filter-row{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
  gap:12px;
  margin-bottom:16px;
}

.filter-row:last-child{margin-bottom:0}

.filter-input, .filter-select{
  padding:10px 12px;
  border-radius:10px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  color:var(--text);
  font-size:14px;
  width:100%;
}

.filter-input:focus, .filter-select:focus{
  outline:none;
  border-color:var(--brand);
  box-shadow:0 0 0 3px rgba(110,168,255,.12);
}

.filter-button{
  padding:10px 20px;
  border:none;
  border-radius:10px;
  background:rgba(255,255,255,.08);
  color:var(--text);
  font-weight:600;
  cursor:pointer;
  transition:all .12s;
  white-space:nowrap;
}

.filter-button:hover{
  background:rgba(255,255,255,.12);
  transform:translateY(-1px);
}

.filter-button.primary{
  background:linear-gradient(135deg, var(--brand), var(--brand2));
  color:white;
}

.filter-button.primary:hover{transform:translateY(-2px)}

.filter-button.danger{
  background:rgba(255,107,107,.15);
  border:1px solid rgba(255,107,107,.3);
  color:#ff6b6b;
}

.filter-tag button{
  background:none;
  border:none;
  color:var(--brand);
  cursor:pointer;
  padding:0;
  font-size:16px;
  line-height:1;
}

.modal-overlay{
  position:fixed;
  top:0;
  left:0;
  right:0;
  bottom:0;
  background:rgba(0,0,0,.7);
  backdrop-filter:blur(4px);
  display:none;
  align-items:center;
  justify-content:center;
  z-index:1000;
  padding:20px;
}
.modal-overlay.active{display:flex}
.modal{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:18px;
  max-width:600px;
  width:100%;
  max-height:80vh;
  overflow-y:auto;
  padding:24px;
}
.modal h2{margin:0 0 16px 0; font-size:20px}
.modal-close{
  float:right;
  background:none;
  border:none;
  color:var(--muted);
  font-size:24px;
  cursor:pointer;
  padding:0;
  line-height:1;
}
.checkbox-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));
  gap:8px;
  margin-top:12px;
}
.checkbox-item{
  display:flex;
  align-items:center;
  gap:8px;
  padding:8px 10px;
  background:rgba(255,255,255,.04);
  border:1px solid var(--line);
  border-radius:8px;
  cursor:pointer;
  transition:all .12s;
}
.checkbox-item:hover{background:rgba(255,255,255,.08); border-color:var(--brand)}
.checkbox-item input{cursor:pointer}
.checkbox-item label{cursor:pointer; font-size:13px; flex:1}

.results-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:16px;
  flex-wrap:wrap;
  gap:12px;
}
.result-count{
  font-size:14px;
  color:var(--muted);
}

.sort-group{
  display:flex;
  gap:12px;
  align-items:center;
  flex-wrap:wrap;
}

.sort-tabs{
  display:flex;
  gap:8px;
  padding:4px;
  background:rgba(255,255,255,.04);
  border-radius:10px;
  border:1px solid var(--line);
}

.sort-tab{
  padding:8px 16px;
  background:none;
  border:none;
  border-radius:7px;
  color:var(--muted);
  font-size:13px;
  font-weight:600;
  cursor:pointer;
  transition:all .12s;
}

.sort-tab:hover{
  background:rgba(255,255,255,.06);
  color:var(--text);
}

.sort-tab.active{
  background:var(--brand);
  color:white;
}

.sort-dropdown select{
  padding:8px 32px 8px 12px;
  border-radius:8px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  color:var(--text);
  font-size:13px;
  cursor:pointer;
  min-width:200px;
}

</style>
@endpush

@section('content')
<div class="wrap">
  <div class="search-hero">
    <h1>🔍 Advanced Search</h1>
    <div style="color:var(--muted); font-size:14px">Find exactly what you're looking for</div>
  </div>

  <form method="GET" action="{{ route('search.advanced') }}" id="searchForm">
    {{-- Main Filter Bar --}}
    <div class="filter-container">
  {{-- Row 1: Search and Quick Filters --}}
  <div class="filter-row">
    <input type="text" name="search" class="filter-input" placeholder="🔎 Search game name..." value="{{ request('search') }}" style="grid-column: span 2">
    
    <button type="button" class="filter-button" onclick="openModal('genreModal')">
      🎮 Genres
    </button>
    
    <button type="button" class="filter-button" onclick="openModal('categoryModal')">
      ⭐ Categories
    </button>
  </div>

  {{-- Row 2: Price, More Filters, Search --}}
  <div class="filter-row">
    <button type="button" class="filter-button" onclick="openModal('priceModal')">
      💰 Price Range
    </button>

    <button type="button" class="filter-button" onclick="openModal('moreModal')">
      ⚙️ More Filters
    </button>

    <button type="submit" class="filter-button primary">
      Search
    </button>
  </div>

  {{-- Row 3: Clear Button (only when filters active) --}}
  @if(request()->hasAny(['search', 'genres', 'categories', 'price_min', 'price_max', 'developer', 'publisher', 'min_review_score', 'platforms', 'intensity_tier', 'min_year', 'max_year', 'free_only', 'min_metacritic', 'controller_support', 'with_achievements', 'family_friendly', 'has_dlc']))
    <div class="filter-row" style="grid-template-columns:1fr auto">
      <div></div>
      <a href="{{ route('search.advanced') }}" class="filter-button danger" style="text-decoration:none">
        Clear All Filters
      </a>
    </div>
  @endif
</div>


    {{-- Active Filters Display --}}
    @if(request()->hasAny(['genres', 'categories', 'price_min', 'price_max', 'developer', 'free_only']))
      <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px">
        @foreach((array)request('genres', []) as $genre)
          <span class="filter-tag">
            {{ $genre }}
            <button type="button" onclick="removeFilter('genres', '{{ $genre }}')">×</button>
          </span>
        @endforeach
        
        @foreach((array)request('categories', []) as $cat)
          <span class="filter-tag">
            {{ $cat }}
            <button type="button" onclick="removeFilter('categories', '{{ $cat }}')">×</button>
          </span>
        @endforeach
        
        @if(request('price_min') || request('price_max'))
          <span class="filter-tag">
            ${{ request('price_min', 0) }} - ${{ request('price_max', '∞') }}
            <button type="button" onclick="removeFilter('price')">×</button>
          </span>
        @endif
        
        @if(request('developer'))
          <span class="filter-tag">
            Dev: {{ request('developer') }}
            <button type="button" onclick="removeFilter('developer')">×</button>
          </span>
        @endif
        
        @if(request('free_only'))
          <span class="filter-tag">
            Free Only
            <button type="button" onclick="removeFilter('free_only')">×</button>
          </span>
        @endif
      </div>
    @endif

    {{-- Hidden inputs for checkboxes --}}
    <div id="hiddenInputs"></div>
  </form>

  {{-- Results --}}
  @if($apps->total() > 0)
  <div class="results-header">
    <div class="result-count">📊 Found {{ $apps->total() }} games</div>

    <div class="sort-group">
      <span style="font-size:13px; color:var(--muted)">Sort by:</span>

      <div class="sort-tabs">
        <button type="button" class="sort-tab {{ !request('sort') || request('sort') === 'recommendations_desc' ? 'active' : '' }}" onclick="changeSort('recommendations_desc')">
          Popular
        </button>
        <button type="button" class="sort-tab {{ request('sort') === 'rating_desc' ? 'active' : '' }}" onclick="changeSort('rating_desc')">
          Top Rated
        </button>
        <button type="button" class="sort-tab {{ request('sort') === 'date_desc' ? 'active' : '' }}" onclick="changeSort('date_desc')">
          Newest
        </button>
        <button type="button" class="sort-tab" onclick="toggleSortDropdown()">
          More ▾
        </button>
      </div>

      <div class="sort-dropdown" id="sortDropdown" style="display:none">
        <select onchange="changeSort(this.value)">
          <optgroup label="📊 Popularity">
            <option value="recommendations_desc" {{ request('sort') === 'recommendations_desc' ? 'selected' : '' }}>Most Recommended</option>
            <option value="popularity_desc" {{ request('sort') === 'popularity_desc' ? 'selected' : '' }}>Most Popular</option>
            <option value="reviews_desc" {{ request('sort') === 'reviews_desc' ? 'selected' : '' }}>Most Reviewed</option>
          </optgroup>
          <optgroup label="⭐ Rating">
            <option value="rating_desc" {{ request('sort') === 'rating_desc' ? 'selected' : '' }}>Highest Rated</option>
            <option value="metacritic_desc" {{ request('sort') === 'metacritic_desc' ? 'selected' : '' }}>Metacritic Score</option>
            <option value="achievements_desc" {{ request('sort') === 'achievements_desc' ? 'selected' : '' }}>Most Achievements</option>
          </optgroup>
          <optgroup label="📅 Release Date">
            <option value="date_desc" {{ request('sort') === 'date_desc' ? 'selected' : '' }}>Newest First</option>
            <option value="date_asc" {{ request('sort') === 'date_asc' ? 'selected' : '' }}>Oldest First</option>
          </optgroup>
          <optgroup label="💰 Price">
            <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
            <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
          </optgroup>
          <optgroup label="🖥️ Hardware">
            <option value="low_end_first" {{ request('sort') === 'low_end_first' ? 'selected' : '' }}>Low-End Friendly</option>
            <option value="high_end_first" {{ request('sort') === 'high_end_first' ? 'selected' : '' }}>High-End Games</option>
          </optgroup>
          <optgroup label="🔤 Name">
            <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name: A-Z</option>
          </optgroup>
        </select>
      </div>
    </div>
  </div>
@endif


  @if($apps->count() === 0)
    @if(request()->hasAny(['search', 'genres', 'categories', 'developer', 'publisher', 'price_min', 'price_max', 'free_only', 'min_review_score', 'release_from', 'release_to', 'platforms', 'intensity_tier', 'min_year', 'max_year']))
      <div class="empty" style="padding:60px 20px; text-align:center; border:1px dashed var(--line); border-radius:16px">
        <div style="font-size:64px; margin-bottom:12px">🎮</div>
        <div style="font-size:18px; margin-bottom:8px; color:var(--text)">No games found</div>
        <div style="color:var(--muted)">Try adjusting your filters or search terms</div>
      </div>
    @else
      <div class="empty" style="padding:60px 20px; text-align:center; border:1px dashed var(--line); border-radius:16px">
        <div style="font-size:64px; margin-bottom:12px">🔍</div>
        <div style="font-size:18px; margin-bottom:8px; color:var(--text)">Start Your Search</div>
        <div style="color:var(--muted); margin-bottom:16px">Use the filters above to discover games</div>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap">
          <span style="padding:8px 16px; background:rgba(110,168,255,.1); border:1px solid rgba(110,168,255,.3); border-radius:10px; font-size:14px">💻 Filter by hardware specs</span>
          <span style="padding:8px 16px; background:rgba(110,168,255,.1); border:1px solid rgba(110,168,255,.3); border-radius:10px; font-size:14px">🎨 Browse by genre</span>
          <span style="padding:8px 16px; background:rgba(110,168,255,.1); border:1px solid rgba(110,168,255,.3); border-radius:10px; font-size:14px">💰 Find deals</span>
        </div>
      </div>
    @endif
  @else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:16px">
      @foreach($apps as $app)
        <a class="game-card" href="{{ route('apps.show', $app) }}">
          <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300?text=No+Image' }}" alt="">
          <div class="p">
            <div class="title">{{ $app->name }}</div>
            <div class="meta">
              @if($app->genres->isNotEmpty())
                {{ $app->genres->pluck('name')->take(2)->join(' · ') }}
              @elseif($app->categories->isNotEmpty())
                {{ $app->categories->pluck('name')->take(2)->join(' · ') }}
              @else
                {{ ucfirst($app->type) }}
              @endif
            </div>
            <div class="badges">
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
                <span class="pill {{ $reviewClass }}">{{ $app->reviews->review_score_desc }}</span>
              @endif
            </div>
          </div>
        </a>
      @endforeach
    </div>

    <div class="pager">
      {{ $apps->links('pagination.steam-dark') }}
    </div>
  @endif
</div>

{{-- Modals --}}
{{-- Genre Modal --}}
<div class="modal-overlay" id="genreModal" onclick="if(event.target===this) closeModal('genreModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('genreModal')">×</button>
    <h2>Select Genres</h2>
    <div class="checkbox-grid" id="genreCheckboxes">
      @foreach($allGenres as $genre)
        <div class="checkbox-item">
          <input type="checkbox" class="genre-checkbox" value="{{ $genre->name }}" id="g_{{ $genre->id }}"
            {{ in_array($genre->name, (array)request('genres', [])) ? 'checked' : '' }}>
          <label for="g_{{ $genre->id }}">{{ $genre->name }}</label>
        </div>
      @endforeach
    </div>
    <button onclick="applyModal('genreModal')" style="width:100%; margin-top:20px; padding:12px; background:linear-gradient(135deg, var(--brand), var(--brand2)); border:none; border-radius:10px; color:white; font-weight:600; cursor:pointer">
      Apply
    </button>
  </div>
</div>

{{-- Category Modal --}}
<div class="modal-overlay" id="categoryModal" onclick="if(event.target===this) closeModal('categoryModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('categoryModal')">×</button>
   <h2>Select Categories</h2>
    <div class="checkbox-grid" id="categoryCheckboxes">
      @foreach($allCategories as $cat)
        <div class="checkbox-item">
          <input type="checkbox" class="category-checkbox" value="{{ $cat->name }}" id="c_{{ $cat->id }}"
            {{ in_array($cat->name, (array)request('categories', [])) ? 'checked' : '' }}>
          <label for="c_{{ $cat->id }}">{{ $cat->name }}</label>
        </div>
      @endforeach
    </div>
    <button onclick="applyModal('categoryModal')" style="width:100%; margin-top:20px; padding:12px; background:linear-gradient(135deg, var(--brand), var(--brand2)); border:none; border-radius:10px; color:white; font-weight:600; cursor:pointer">
      Apply
    </button>
  </div>
</div>

{{-- Price Modal --}}
<div class="modal-overlay" id="priceModal" onclick="if(event.target===this) closeModal('priceModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('priceModal')">×</button>
    <h2>Price Range</h2>
    <div style="display:grid; gap:16px; margin-top:16px">
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">Minimum Price ($)</label>
        <input type="number" name="price_min" placeholder="0" min="0" value="{{ request('price_min') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
      </div>
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">Maximum Price ($)</label>
        <input type="number" name="price_max" placeholder="100" min="0" value="{{ request('price_max') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
      </div>
      <div class="checkbox-item">
        <input type="checkbox" name="free_only" value="1" id="free_only" {{ request('free_only') ? 'checked' : '' }}>
        <label for="free_only">Free games only</label>
      </div>
    </div>
    <button onclick="applyModal('priceModal')" style="width:100%; margin-top:20px; padding:12px; background:linear-gradient(135deg, var(--brand), var(--brand2)); border:none; border-radius:10px; color:white; font-weight:600; cursor:pointer">
      Apply
    </button>
  </div>
</div>

{{-- More Filters Modal --}}
<div class="modal-overlay" id="moreModal" onclick="if(event.target===this) closeModal('moreModal')">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('moreModal')">×</button>
    <h2>More Filters</h2>
    <div style="display:grid; gap:16px; margin-top:16px">
      {{-- Hardware Intensity Tier --}}
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">🎮 Hardware Intensity</label>
        <select name="intensity_tier" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
          <option value="">All Intensities</option>
          <option value="light" {{ request('intensity_tier') === 'light' ? 'selected' : '' }}>💻 Light (Low Specs)</option>
          <option value="medium" {{ request('intensity_tier') === 'medium' ? 'selected' : '' }}>⚙️ Medium</option>
          <option value="high-end" {{ request('intensity_tier') === 'high-end' ? 'selected' : '' }}>🔥 High-End</option>
          <option value="ultra" {{ request('intensity_tier') === 'ultra' ? 'selected' : '' }}>💎 Ultra (Demanding)</option>
        </select>
      </div>

      {{-- Platform Multi-Select --}}
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">🖥️ Platforms (select multiple)</label>
        <div style="display:flex; gap:8px; flex-wrap:wrap">
          <div class="checkbox-item" style="flex:1; min-width:120px">
            <input type="checkbox" name="platforms[]" value="pc" id="platform_pc" {{ in_array('pc', (array)request('platforms', [])) ? 'checked' : '' }}>
            <label for="platform_pc">🪟 Windows</label>
          </div>
          <div class="checkbox-item" style="flex:1; min-width:120px">
            <input type="checkbox" name="platforms[]" value="mac" id="platform_mac" {{ in_array('mac', (array)request('platforms', [])) ? 'checked' : '' }}>
            <label for="platform_mac">🍎 macOS</label>
          </div>
          <div class="checkbox-item" style="flex:1; min-width:120px">
            <input type="checkbox" name="platforms[]" value="linux" id="platform_linux" {{ in_array('linux', (array)request('platforms', [])) ? 'checked' : '' }}>
            <label for="platform_linux">🐧 Linux</label>
          </div>
        </div>
      </div>

      {{-- Release Year Range --}}
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
        <div>
          <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">From Year</label>
          <input type="number" name="min_year" placeholder="2000" min="1990" max="{{ date('Y') + 1 }}" value="{{ request('min_year') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
        </div>
        <div>
          <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">To Year</label>
          <input type="number" name="max_year" placeholder="{{ date('Y') }}" min="1990" max="{{ date('Y') + 1 }}" value="{{ request('max_year') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
        </div>
      </div>

      {{-- Minimum Review Score --}}
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">Minimum Review Score (%)</label>
        <input type="number" name="min_review_score" placeholder="0-100" min="0" max="100" value="{{ request('min_review_score') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
      </div>

      {{-- Metacritic Score --}}
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">Minimum Metacritic Score</label>
        <select name="min_metacritic" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
          <option value="">Any Score</option>
          <option value="90" {{ request('min_metacritic') == 90 ? 'selected' : '' }}>90+ (Universal Acclaim)</option>
          <option value="80" {{ request('min_metacritic') == 80 ? 'selected' : '' }}>80+ (Generally Favorable)</option>
          <option value="75" {{ request('min_metacritic') == 75 ? 'selected' : '' }}>75+</option>
          <option value="70" {{ request('min_metacritic') == 70 ? 'selected' : '' }}>70+</option>
          <option value="60" {{ request('min_metacritic') == 60 ? 'selected' : '' }}>60+</option>
          <option value="50" {{ request('min_metacritic') == 50 ? 'selected' : '' }}>50+ (Mixed)</option>
        </select>
      </div>

      {{-- Controller Support --}}
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">🎮 Controller Support</label>
        <select name="controller_support" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
          <option value="">Any</option>
          <option value="full" {{ request('controller_support') === 'full' ? 'selected' : '' }}>Full Controller Support</option>
          <option value="partial" {{ request('controller_support') === 'partial' ? 'selected' : '' }}>Partial Support</option>
          <option value="any" {{ request('controller_support') === 'any' ? 'selected' : '' }}>Any Controller Support</option>
        </select>
      </div>

      {{-- Additional Options --}}
      <div style="display:flex; flex-direction:column; gap:8px">
        <div class="checkbox-item">
          <input type="checkbox" name="with_achievements" id="with_achievements" value="1" {{ request('with_achievements') ? 'checked' : '' }}>
          <label for="with_achievements">🏆 Has Achievements</label>
        </div>
        <div class="checkbox-item">
          <input type="checkbox" name="family_friendly" id="family_friendly" value="1" {{ request('family_friendly') ? 'checked' : '' }}>
          <label for="family_friendly">👨‍👩‍👧‍👦 Family Friendly (No Adult Content)</label>
        </div>
        <div class="checkbox-item">
          <input type="checkbox" name="has_dlc" id="has_dlc" value="1" {{ request('has_dlc') ? 'checked' : '' }}>
          <label for="has_dlc">📦 Has DLC</label>
        </div>
      </div>

      {{-- Release Date Range --}}
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">Release Date From</label>
        <input type="date" name="release_from" value="{{ request('release_from') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
      </div>
      <div>
        <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--muted)">Release Date To</label>
        <input type="date" name="release_to" value="{{ request('release_to') }}" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); background:rgba(255,255,255,.06); color:var(--text)">
      </div>
    </div>
    <button onclick="applyModal('moreModal')" style="width:100%; margin-top:20px; padding:12px; background:linear-gradient(135deg, var(--brand), var(--brand2)); border:none; border-radius:10px; color:white; font-weight:600; cursor:pointer">
      Apply
    </button>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).classList.add('active');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

function applyModal(modalId) {
  const form = document.getElementById('searchForm');
  const hiddenInputs = document.getElementById('hiddenInputs');

  // Clear previous hidden inputs
  hiddenInputs.innerHTML = '';

  // Handle genre checkboxes
  if (modalId === 'genreModal') {
    const checkedGenres = document.querySelectorAll('.genre-checkbox:checked');
    checkedGenres.forEach(checkbox => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'genres[]';
      input.value = checkbox.value;
      hiddenInputs.appendChild(input);
    });
  }

  // Handle category checkboxes
  if (modalId === 'categoryModal') {
    const checkedCategories = document.querySelectorAll('.category-checkbox:checked');
    checkedCategories.forEach(checkbox => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'categories[]';
      input.value = checkbox.value;
      hiddenInputs.appendChild(input);
    });
  }

  // For other modals, copy all form inputs from the modal
  if (modalId === 'priceModal' || modalId === 'moreModal') {
    const modal = document.getElementById(modalId);
    const modalInputs = modal.querySelectorAll('input, select');
    modalInputs.forEach(input => {
      if (input.type === 'checkbox' && input.checked) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = input.name;
        hiddenInput.value = input.value;
        hiddenInputs.appendChild(hiddenInput);
      } else if (input.type !== 'checkbox' && input.value) {
        // Check if input already exists in main form
        let existingInput = form.querySelector(`[name="${input.name}"]`);
        if (!existingInput || existingInput.closest('.modal-overlay')) {
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = input.name;
          hiddenInput.value = input.value;
          hiddenInputs.appendChild(hiddenInput);
        } else {
          existingInput.value = input.value;
        }
      }
    });
  }

  closeModal(modalId);
  form.submit();
}

function removeFilter(type, value) {
  const form = document.getElementById('searchForm');

  if (type === 'genres') {
    // Uncheck the genre checkbox in modal
    const checkbox = document.querySelector(`.genre-checkbox[value="${value}"]`);
    if (checkbox) checkbox.checked = false;
  } else if (type === 'categories') {
    // Uncheck the category checkbox in modal
    const checkbox = document.querySelector(`.category-checkbox[value="${value}"]`);
    if (checkbox) checkbox.checked = false;
  } else if (type === 'price') {
    const priceMinInput = document.querySelector('input[name="price_min"]');
    const priceMaxInput = document.querySelector('input[name="price_max"]');
    if (priceMinInput) priceMinInput.value = '';
    if (priceMaxInput) priceMaxInput.value = '';
  } else {
    const input = document.querySelector(`[name="${type}"]`);
    if (input) input.value = '';
  }

  form.submit();
}

// On page load, sync existing genres/categories from URL to checkboxes
document.addEventListener('DOMContentLoaded', () => {
  // Get current genres from URL
  const urlParams = new URLSearchParams(window.location.search);
  const genres = urlParams.getAll('genres[]');
  const categories = urlParams.getAll('categories[]');

  // Check genre checkboxes
  genres.forEach(genre => {
    const checkbox = document.querySelector(`.genre-checkbox[value="${genre}"]`);
    if (checkbox) checkbox.checked = true;
  });

  // Check category checkboxes
  categories.forEach(category => {
    const checkbox = document.querySelector(`.category-checkbox[value="${category}"]`);
    if (checkbox) checkbox.checked = true;
  });
});
function changeSort(sortValue) {
  const url = new URL(window.location);
  url.searchParams.set('sort', sortValue);
  window.location.href = url.toString();
}

function toggleSortDropdown() {
  const dropdown = document.getElementById('sortDropdown');
  dropdown.style.display = dropdown.style.display === 'none' ? 'inline-block' : 'none';
}

</script>
@endsection