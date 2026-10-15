@extends('layouts.app')

@section('title', 'Can I Run It? Steam PC Compatibility Checker – GameSpecMatch')
@section('meta_description', 'Use GameSpecMatch to check if your PC can run any Steam game. Compare your CPU, GPU, RAM, storage, and DirectX vs minimum and recommended requirements in seconds.')
@section('canonical', url('/'))

@section('content')
<div class="wrap">
    {{-- Hero Section --}}
    <div class="hero">
        <div class="hero-content">
            <h1>Can I Run It? Steam PC Game Compatibility Checker</h1>
            <p class="tagline">Find PC Games Your System Can Actually Run</p>
            <p class="tagline" style="font-size:18px; color:rgba(255,255,255,.65)">
                Check system requirements, compare hardware specs, and discover compatible Steam games instantly
            </p>
            <div class="stats">
                <div class="stat">
                    <span class="stat-num">{{ number_format($totalGames ?? 0) }}+</span>
                    <span class="stat-label">Steam Games</span>
                </div>
                <div class="stat">
                    <span class="stat-num">{{ number_format($totalChecks ?? 150000) }}+</span>
                    <span class="stat-label">Compatibility Checks</span>
                </div>
                <div class="stat">
                    <span class="stat-num" style="color:var(--green)">{{ number_format($totalDeals ?? 0) }}</span>
                    <span class="stat-label">Hot Deals Now</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="search-section">
        <div class="search-wrap">
            <input 
                id="q" 
                type="text" 
                class="search-input"
                placeholder="🎮 Search games (e.g., Cyberpunk 2077) or 🖥️ hardware (e.g., RTX 3060, 16GB RAM)..." 
                autocomplete="off"
            >
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <div id="suggestbox" class="suggestbox"></div>
        </div>
    </div>

    {{-- Simple 3-Column CTA Grid --}}
    <div class="cta-grid-simple">
        {{-- Can My PC Run It --}}
        <div class="cta-card-new primary-card" onclick="window.location='{{ route('hardware.add-specs') }}'">
            <span class="cta-icon">🖥️</span>
            <h2>Can My PC Run It?</h2>
            <p>Check your system specs against {{ number_format($totalGames ?? 0) }}+ Steam games instantly.</p>
            <button class="cta-btn">Check Compatibility</button>
        </div>
        
        {{-- Hot Deals --}}
        <div class="cta-card-new deals-card" onclick="window.location='{{ route('deals.index') }}'">
            <span class="cta-icon">🔥</span>
            <h2>Hot Deals</h2>
            <p>Discover games on sale with up to 90% off. Updated daily.</p>
            <button class="cta-btn deals-btn">Browse Deals</button>
        </div>
        
        {{-- All Games --}}
        <div class="cta-card-new" onclick="window.location='{{ route('apps.browse') }}'">
            <span class="cta-icon">🎮</span>
            <h2>All Games</h2>
            <p>Explore {{ number_format($totalGames ?? 0) }}+ games with reviews & specs.</p>
            <button class="cta-btn">Explore Games</button>
        </div>
    </div>

    {{-- Hot Deals Preview --}}
    @if(isset($hotDeals) && $hotDeals->count() > 0)
    <div class="section">
        <div class="section-header">
            <h2><span class="section-emoji">💸</span> Hottest Deals Right Now</h2>
            <a href="{{ route('deals.index') }}" class="section-link">
                See All Deals
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid">
            @foreach($hotDeals as $app)
                <a class="game-card" href="{{ route('apps.show', $app->slug) }}" style="border-color:rgba(255,205,110,.3)">
                    @if($app->pricing && $app->pricing->discount_percent > 0)
                        <div style="position:absolute; top:12px; right:12px; background:linear-gradient(135deg, #ff6b6b, #ff8e53); color:white; font-weight:700; font-size:16px; padding:6px 12px; border-radius:8px; box-shadow:0 4px 12px rgba(255,107,107,.4); z-index:10; animation:pulse 2s infinite">
                            -{{ $app->pricing->discount_percent }}%
                        </div>
                    @endif
                    
                    <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text=' . urlencode($app->name) }}" alt="{{ $app->name }}" loading="lazy">
                    
                    <div class="card-content">
                        <div class="title">{{ $app->name }}</div>
                        <div class="meta">
                            @if($app->pricing)
                                <span style="text-decoration:line-through; opacity:.6">
                                    ${{ number_format($app->pricing->initial_price / 100, 2) }}
                                </span>
                                <span style="color:var(--green); font-weight:700; font-size:18px">
                                    ${{ number_format($app->pricing->final_price / 100, 2) }}
                                </span>
                            @endif
                        </div>
                        <div class="badges">
                            @if($app->pricing)
                                <span class="pill" style="background:rgba(78,255,187,.15); color:var(--green); border:1px solid rgba(78,255,187,.3)">
                                    💰 Save ${{ number_format(($app->pricing->initial_price - $app->pricing->final_price) / 100, 2) }}
                                </span>
                            @endif
                            @if($app->reviews && $app->reviews->total_reviews > 0)
                                @php
                                    $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                                    $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
                                @endphp
                                <span class="pill {{ $reviewClass }}">
                                    {{ round($positivePercent) }}%
                                </span>
                            @endif
                            @if($app->has_metacritic_score)
                                <span class="pill" style="background:{{ $app->metacritic_score >= 75 ? 'rgba(78,255,187,.15)' : ($app->metacritic_score >= 50 ? 'rgba(255,205,110,.15)' : 'rgba(255,107,107,.15)') }}; color:{{ $app->metacritic_score >= 75 ? 'var(--green)' : ($app->metacritic_score >= 50 ? 'var(--orange)' : 'var(--red)') }}; border:1px solid {{ $app->metacritic_score >= 75 ? 'rgba(78,255,187,.3)' : ($app->metacritic_score >= 50 ? 'rgba(255,205,110,.3)' : 'rgba(255,107,107,.3)') }}">
                                    MC {{ $app->metacritic_score }}
                                </span>
                            @endif
                            @if($app->has_controller_support)
                                <span class="pill" title="Controller Support: {{ ucfirst($app->controller_support) }}" style="background:rgba(110,168,255,.15); color:#6ea8ff; border:1px solid rgba(110,168,255,.3)">
                                    🎮
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Most Popular Games This Month --}}
    @if(isset($popularGames) && $popularGames->count() > 0)
    <div class="section">
        <div class="section-header">
            <h2><span class="section-emoji">🔥</span> Most Checked Games This Month</h2>
        </div>
        <div class="grid">
            @foreach($popularGames as $game)
                <a class="game-card" href="{{ route('apps.show', $game->slug) }}">
                    <img class="thumb" src="{{ $game->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text='.urlencode($game->name) }}" alt="{{ $game->name }}" loading="lazy">
                    <div class="card-content">
                        <div class="title">{{ $game->name }}</div>
                        <div class="meta">
                            @if($game->reviews && $game->reviews->total_reviews > 0)
                                @php $percent = round(($game->reviews->total_positive / $game->reviews->total_reviews) * 100); @endphp
                                <span class="pill {{ $percent >= 80 ? 'positive' : ($percent >= 50 ? 'mixed' : 'negative') }}">
                                    {{ $percent }}% Positive
                                </span>
                                <span>{{ number_format($game->reviews->total_reviews) }} reviews</span>
                            @else
                                <span>{{ number_format($game->total_recommendations ?? 0) }} 👍</span>
                            @endif
                        </div>
                        <div class="badges">
                            @if($game->is_free)
                                <span class="pill free">Free</span>
                            @elseif($game->pricing)
                                <span class="pill paid">${{ number_format($game->pricing->final_price / 100, 2) }}</span>
                            @endif
                            @if($game->has_metacritic_score)
                                <span class="pill" style="background:{{ $game->metacritic_score >= 75 ? 'rgba(78,255,187,.15)' : ($game->metacritic_score >= 50 ? 'rgba(255,205,110,.15)' : 'rgba(255,107,107,.15)') }}; color:{{ $game->metacritic_score >= 75 ? 'var(--green)' : ($game->metacritic_score >= 50 ? 'var(--orange)' : 'var(--red)') }}; border:1px solid {{ $game->metacritic_score >= 75 ? 'rgba(78,255,187,.3)' : ($game->metacritic_score >= 50 ? 'rgba(255,205,110,.3)' : 'rgba(255,107,107,.3)') }}">
                                    MC {{ $game->metacritic_score }}
                                </span>
                            @endif
                            @if($game->has_controller_support)
                                <span class="pill" title="Controller Support" style="background:rgba(110,168,255,.15); color:#6ea8ff; border:1px solid rgba(110,168,255,.3)">
                                    🎮
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Most Recommended Games --}}
    @if(isset($mostRecommended) && $mostRecommended->count() > 0)
    <div class="section">
        <div class="section-header">
            <h2><span class="section-emoji">⭐</span> Highest Rated Games</h2>
            <a href="{{ route('apps.popular') }}" class="section-link">
                View All
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid">
            @foreach($mostRecommended as $app)
                <a class="game-card" href="{{ route('apps.show', $app->slug) }}">
                    <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text=' . urlencode($app->name) }}" alt="{{ $app->name }}" loading="lazy">
                    <div class="card-content">
                        <div class="title">{{ $app->name }}</div>
                        <div class="meta">
                            <span>{{ number_format($app->total_recommendations ?? 0) }} 👍</span>
                            @if($app->release_date)
                                <span class="meta-dot"></span>
                                <span>{{ \Carbon\Carbon::parse($app->release_date)->format('Y') }}</span>
                            @endif
                        </div>
                        <div class="badges">
                            @if($app->is_free)
                                <span class="pill free">Free to Play</span>
                            @elseif($app->pricing)
                                @if($app->pricing->discount_percent > 0)
                                    <span class="pill discount">
                                        -{{ $app->pricing->discount_percent }}% 
                                        ${{ number_format($app->pricing->final_price / 100, 2) }}
                                    </span>
                                @else
                                    <span class="pill paid">${{ number_format($app->pricing->final_price / 100, 2) }}</span>
                                @endif
                            @endif

                            @if($app->reviews && $app->reviews->total_reviews > 0)
                                @php
                                    $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                                    $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
                                @endphp
                                <span class="pill {{ $reviewClass }}">
                                    {{ round($positivePercent) }}%
                                </span>
                            @endif
                            @if($app->has_metacritic_score)
                                <span class="pill" style="background:{{ $app->metacritic_score >= 75 ? 'rgba(78,255,187,.15)' : ($app->metacritic_score >= 50 ? 'rgba(255,205,110,.15)' : 'rgba(255,107,107,.15)') }}; color:{{ $app->metacritic_score >= 75 ? 'var(--green)' : ($app->metacritic_score >= 50 ? 'var(--orange)' : 'var(--red)') }}; border:1px solid {{ $app->metacritic_score >= 75 ? 'rgba(78,255,187,.3)' : ($app->metacritic_score >= 50 ? 'rgba(255,205,110,.3)' : 'rgba(255,107,107,.3)') }}">
                                    MC {{ $app->metacritic_score }}
                                </span>
                            @endif
                            @if($app->has_controller_support)
                                <span class="pill" title="Controller Support" style="background:rgba(110,168,255,.15); color:#6ea8ff; border:1px solid rgba(110,168,255,.3)">
                                    🎮
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Latest Releases --}}
    @if(isset($latestReleases) && $latestReleases->count() > 0)
    <div class="section">
        <div class="section-header">
            <h2><span class="section-emoji">🆕</span> Latest Releases</h2>
            <a href="{{ route('apps.latest') }}" class="section-link">
                See All Releases
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid">
            @foreach($latestReleases as $app)
                <a class="game-card" href="{{ route('apps.show', $app->slug) }}">
                    <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text=' . urlencode($app->name) }}" alt="{{ $app->name }}" loading="lazy">
                    <div class="card-content">
                        <div class="title">{{ $app->name }}</div>
                        <div class="meta">
                            <span>Released {{ \Carbon\Carbon::parse($app->release_date)->diffForHumans() }}</span>
                        </div>
                        <div class="badges">
                            @if($app->is_free)
                                <span class="pill free">Free</span>
                            @elseif($app->pricing)
                                @if($app->pricing->discount_percent > 0)
                                    <span class="pill discount">
                                        -{{ $app->pricing->discount_percent }}%
                                    </span>
                                @endif
                                <span class="pill paid">${{ number_format($app->pricing->final_price / 100, 2) }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Free to Play Games --}}
    @if(isset($freeGames) && $freeGames->count() > 0)
    <div class="section">
        <div class="section-header">
            <h2><span class="section-emoji">💰</span> Top Free to Play Games</h2>
            <a href="{{ route('apps.free') }}" class="section-link">
                Browse All Free Games
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="grid">
            @foreach($freeGames as $app)
                <a class="game-card" href="{{ route('apps.show', $app->slug) }}">
                    <img class="thumb" src="{{ $app->header_image ?: 'https://placehold.co/600x300/1a2332/6EA8FF?text=' . urlencode($app->name) }}" alt="{{ $app->name }}" loading="lazy">
                    <div class="card-content">
                        <div class="title">{{ $app->name }}</div>
                        <div class="meta">
                            @if($app->reviews && $app->reviews->total_reviews > 0)
                                <span>{{ number_format($app->reviews->total_reviews) }} reviews</span>
                            @else
                                <span>Free to Play</span>
                            @endif
                        </div>
                        <div class="badges">
                            <span class="pill free">Free to Play</span>
                            @if($app->reviews && $app->reviews->total_reviews > 0)
                                @php
                                    $positivePercent = ($app->reviews->total_positive / $app->reviews->total_reviews) * 100;
                                    $reviewClass = $positivePercent >= 80 ? 'positive' : ($positivePercent >= 50 ? 'mixed' : 'negative');
                                @endphp
                                <span class="pill {{ $reviewClass }}">
                                    {{ round($positivePercent) }}% Positive
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- SEO Content Block --}}
    <div class="seo-content">
        <h2>Complete Steam PC Game Requirements Database</h2>
        <p>
            <strong>Game Spec Match</strong> is the most comprehensive Steam game compatibility checker and system requirements database for PC gamers. Our platform helps you discover which games your computer can run by comparing your hardware against detailed system requirements for over <strong>{{ number_format($totalGames ?? 0) }} Steam games</strong>.
        </p>
        
        <p>
            Whether you're building a new gaming PC, considering an upgrade, or simply want to know if your current setup can handle the latest AAA titles, Game Spec Match provides instant, accurate compatibility analysis. Our database is updated daily with pricing information, community review scores, and detailed hardware specifications.
        </p>
        
        <h3>Why Choose Game Spec Match?</h3>
        <ul>
            <li><strong>Instant Compatibility Checks:</strong> Test your PC specs against minimum and recommended requirements for any Steam game in seconds</li>
            <li><strong>Comprehensive Requirements Data:</strong> View detailed CPU, GPU, RAM, storage, and DirectX specifications with performance estimates</li>
            <li><strong>Smart Hardware Search:</strong> Find games by searching for your specific components (e.g., "RTX 3060 games" or "16GB RAM compatible")</li>
            <li><strong>Real-Time Pricing:</strong> See current Steam prices, active discounts, and free-to-play options across the entire catalog</li>
            <li><strong>Community-Driven Reviews:</strong> Access aggregated review scores and recommendations from millions of Steam players</li>
            <li><strong>Latest Game Releases:</strong> Stay updated with newly released PC games and upcoming titles with full system requirements</li>
            <li><strong>Performance Predictions:</strong> Get FPS estimates and bottleneck warnings based on your hardware configuration</li>
            <li><strong>Save Your Setup:</strong> Create hardware profiles to instantly check compatibility across thousands of games</li>
        </ul>

        <h3>How It Works</h3>
        <p>
            Game Spec Match uses advanced algorithms to compare your PC components against official Steam game requirements. Simply <a href="{{ route('hardware.add-specs') }}">enter your system specifications</a> or let us detect them automatically. We'll instantly show you which games you can run smoothly, which might struggle, and where your PC excels.
        </p>

        <p>
            Our compatibility checker goes beyond simple yes/no answers. We provide detailed analysis of potential bottlenecks, suggest optimal graphics settings, and recommend hardware upgrades that will give you the best gaming performance boost for your budget. Browse our curated collections of <a href="{{ route('apps.free') }}">free-to-play games</a>, <a href="{{ route('apps.popular') }}">highest-rated titles</a>, and <a href="{{ route('apps.latest') }}">latest releases</a> to find your next gaming adventure.
        </p>
    </div>
</div>

<script>
const q = document.getElementById('q');
const box = document.getElementById('suggestbox');
let searchTimeout = null;

// Autocomplete functionality
q.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    const val = q.value.trim();
    
    if (!val || val.length < 2) { 
        box.style.display = 'none'; 
        box.innerHTML = ''; 
        return; 
    }

    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`{{ route('apps.suggest') }}?q=${encodeURIComponent(val)}`);
            const data = await res.json();
            
            if (!data.length) { 
                box.style.display = 'none'; 
                box.innerHTML = ''; 
                return; 
            }

            box.innerHTML = data.slice(0, 8).map(x => `
                <a href="{{ url('/game') }}/${x.slug}" class="suggest-item">
                    <span class="suggest-name">${escapeHtml(x.name)}</span>
                    <span class="badge">#${x.app_id}</span>
                </a>
            `).join('');
            box.style.display = 'block';
        } catch(err) {
            console.error('Search error:', err);
        }
    }, 200);
});

// Enter key to search
q.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        const val = q.value.trim();
        if (val) {
            window.location.href = `{{ route('apps.browse') }}?q=${encodeURIComponent(val)}`;
        }
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    if (!box.contains(e.target) && e.target !== q) {
        box.style.display = 'none';
    }
});

// HTML escape function
function escapeHtml(str){
    return (str ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[m]));
}
</script>
@endsection