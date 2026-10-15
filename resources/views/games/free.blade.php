@extends('layouts.app')

@section('content')
<div class="wrap">

  <!-- Page Header -->
  <div class="page-header">
    <h1>💰 Free to Play Games</h1>
    <div class="sub-text">{{ number_format($apps->total()) }} free games available</div>
  </div>

  <!-- Filters Bar -->
  <form method="GET" action="{{ route('apps.free') }}" class="filters-bar">
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

    <div>
      <label>Sort By</label>
      <select name="sort" onchange="this.form.submit()">
        <option value="recommendations" {{ request('sort', 'recommendations') === 'recommendations' ? 'selected' : '' }}>Most Recommended</option>
        <option value="reviews" {{ request('sort') === 'reviews' ? 'selected' : '' }}>Most Reviewed</option>
        <option value="date" {{ request('sort') === 'date' ? 'selected' : '' }}>Newest First</option>
        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name A-Z</option>
      </select>
    </div>

    @if(request()->hasAny(['genre', 'sort']))
      <a href="{{ route('apps.free') }}" class="clear-btn">Clear</a>
    @endif
  </form>

  <!-- Games Grid -->
  <div class="grid">
    @foreach($apps as $app)
      <a class="game-card" href="{{ route('apps.show', ['app' => $app->app_id]) }}">
        <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text='.urlencode($app->name) }}" alt="{{ $app->name }}">
        <div class="p">
          <div class="title">{{ $app->name }}</div>
          
          <div class="meta">
            <span class="pill free">Free to Play</span>

            @if($app->reviews && $app->reviews->total_reviews > 0)
              @php
                $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
              @endphp
              <span class="pill {{ $reviewClass }}">{{ round($positivePercent) }}%</span>
              <span class="review-count">{{ number_format($app->reviews->total_reviews) }} reviews</span>
            @endif

            @if($app->genres->isNotEmpty())
              @foreach($app->genres as $genre)
                <span class="pill">{{ $genre->name }}</span>
              @endforeach
            @endif
          </div>
        </div>
      </a>
    @endforeach
  </div>

  <!-- Pagination -->
  <div class="pager">
    {{ $apps->links('pagination.steam-dark') }}
  </div>

</div>
@endsection
