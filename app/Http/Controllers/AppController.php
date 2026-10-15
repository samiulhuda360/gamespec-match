<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\CPU;
use App\Models\GPU;
use App\Models\StatsTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    // ========================================
    // CONFIGURATION CONSTANTS
    // ========================================
    
    const CACHE_DURATION_SHORT = 1800;  // 30 minutes
    const CACHE_DURATION_LONG = 3600;   // 1 hour
    const ITEMS_PER_PAGE = 32;
    const HOMEPAGE_ITEMS_LIMIT = 8;
    const AUTOCOMPLETE_LIMIT = 10;
    
    const HARDWARE_KEYWORDS = [
        'rtx', 'gtx', 'nvidia', 'geforce', 'radeon', 'rx', 'amd', 'intel', 'arc',
        'titan', 'quadro', 'vega', 'ryzen', 'core', 'i3', 'i5', 'i7', 'i9',
        'threadripper', 'xeon', 'pentium', 'celeron', 'athlon',
        'gb ram', 'gb memory', '8gb', '16gb', '32gb', '64gb', 'ddr3', 'ddr4', 'ddr5',
        'storage', 'ssd', 'hdd', 'nvme', 'directx', 'dx11', 'dx12', 'dx9', 'dx10'
    ];

    // ========================================
    // HOMEPAGE
    // ========================================
    
    public function index()
    {
        $stats = $this->getHomepageStats();
        
        return view('apps.index', [
            'totalGames' => $stats['totalGames'],
            'totalChecks' => $stats['totalChecks'],
            'totalDeals' => $stats['totalDeals'],
            'popularGames' => $this->getPopularGames(),
            'mostRecommended' => $this->getMostRecommended(),
            'latestReleases' => $this->getLatestReleases(),
            'freeGames' => $this->getFreeGames(),
            'hotDeals' => $this->getHotDeals(),
        ]);
    }

    // ========================================
    // GAME DETAIL PAGE
    // ========================================
    
    public function show(App $app)
    {
        // Eager load all relationships at once
        $app->load([
            'genres',
            'categories',
            'sysRequirements',
            'screenshots',
            'movies',
            'reviews',
            'pricing'
        ]);

        // Track view
        StatsTracking::track('game_view', $app->app_id);
        $app->increment('total_views');

        // Organize system requirements by platform
        $reqByPlatform = $app->sysRequirements->keyBy('platform');

        // Get featured trailer
        $featuredMovie = $app->movies->firstWhere('highlight', 1) ?? $app->movies->first();

        // Get active hardware profile
        $activeHardware = $this->getActiveHardware();

        // Get similar games based on genres (new feature!)
        $similarGames = $this->getSimilarGames($app);

        // Hardware dropdown options
        $hardwareOptions = $this->getHardwareOptions();

        return view('apps.show', [
            'app' => $app,
            'reqByPlatform' => $reqByPlatform,
            'featuredMovie' => $featuredMovie,
            'activeHardware' => $activeHardware,
            'similarGames' => $similarGames,
            ...$hardwareOptions
        ]);
    }

    // ========================================
    // BROWSE PAGES
    // ========================================
    
    public function browse(Request $request)
    {
        $query = App::query()->with(['reviews', 'pricing', 'genres']);

        // Apply filters
        $this->applySearchFilter($query, $request->input('search'));
        $this->applyFreeFilter($query, $request->input('free'));
        $this->applyPriceFilter($query, $request);
        $this->applyGenreFilter($query, $request->input('genre'));
        $this->applyCategoryFilter($query, $request->input('category'));
        
        // Apply sorting
        $this->applySorting($query, $request->input('sort', 'recommendations'));

        $apps = $query->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $genres = $this->getGenresList();
        $categories = $this->getCategoriesList();

        return view('games.browse', compact('apps', 'genres', 'categories'));
    }

    public function free(Request $request)
    {
        $query = App::query()
            ->with(['reviews', 'genres'])
            ->where('is_free', true);

        $this->applyGenreFilter($query, $request->input('genre'));
        $this->applySorting($query, $request->input('sort', 'recommendations'));

        $apps = $query->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $genres = $this->getGenresList();

        return view('games.free', compact('apps', 'genres'));
    }

    public function latest(Request $request)
    {
        $period = $request->input('period', 'month');
        
        $query = App::query()
            ->with(['reviews', 'pricing', 'genres'])
            ->whereNotNull('release_date')
            ->where('release_date', '<=', now());

        $this->applyPeriodFilter($query, $period);
        $this->applyGenreFilter($query, $request->input('genre'));
        
        $query->orderByDesc('release_date');

        $apps = $query->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $genres = $this->getGenresList();

        return view('games.latest', compact('apps', 'genres', 'period'));
    }

    public function popular(Request $request)
    {
        $period = $request->input('period', 'all');
        
        $query = App::query()
            ->with(['reviews', 'pricing', 'genres'])
            ->whereNotNull('total_recommendations')
            ->where('total_recommendations', '>', 0);

        $this->applyPeriodFilter($query, $period, 'release');
        $this->applyGenreFilter($query, $request->input('genre'));
        $this->applySorting($query, $request->input('sort', 'recommendations'));

        $apps = $query->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $genres = $this->getGenresList();

        return view('games.popular', compact('apps', 'genres', 'period'));
    }

    // ========================================
    // AUTOCOMPLETE SEARCH
    // ========================================
    
    public function suggest(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $searchLower = strtolower($q);
        $isHardwareSearch = $this->isHardwareSearch($searchLower);

        if ($isHardwareSearch) {
            $items = $this->searchByHardware($searchLower);
        } else {
            $items = $this->searchByName($q);
        }

        return response()->json($items);
    }

    // ========================================
    // PRIVATE HELPER METHODS - HOMEPAGE DATA
    // ========================================
    
    private function getHomepageStats()
    {
        return Cache::remember('homepage_stats', self::CACHE_DURATION_SHORT, function () {
            return [
                'totalGames' => App::count(),
                'totalChecks' => StatsTracking::where('stat_type', 'compatibility_check')->count(),
                'totalViews' => StatsTracking::where('stat_type', 'game_view')->count(),
                'totalProfiles' => DB::table('user_hardware')->count(),
                'totalDeals' => App::whereHas('pricing', function($q) {
                    $q->where('discount_percent', '>=', 75)
                      ->where('final_price', '>', 0);
                })->count(),
            ];
        });
    }

    private function getPopularGames()
    {
        return Cache::remember('popular_games', self::CACHE_DURATION_SHORT, function () {
            $appIds = StatsTracking::where('stat_type', 'game_view')
                ->where('created_at', '>=', now()->subDays(30))
                ->select('app_id', DB::raw('COUNT(*) as view_count'))
                ->whereNotNull('app_id')
                ->groupBy('app_id')
                ->orderByDesc('view_count')
                ->limit(12)
                ->pluck('app_id');
            
            if ($appIds->isEmpty()) {
                return App::with(['pricing', 'reviews'])
                    ->orderByDesc('total_recommendations')
                    ->limit(12)
                    ->get();
            }
            
            return App::with(['pricing', 'reviews'])
                ->whereIn('app_id', $appIds)
                ->get()
                ->sortBy(fn($app) => $appIds->search($app->app_id));
        });
    }

    private function getMostRecommended()
    {
        return Cache::remember('most_recommended', self::CACHE_DURATION_LONG, function () {
            return App::with(['pricing', 'reviews'])
                ->whereNotNull('total_recommendations')
                ->where('total_recommendations', '>', 0)
                ->orderByDesc('total_recommendations')
                ->limit(self::HOMEPAGE_ITEMS_LIMIT)
                ->get();
        });
    }

    private function getLatestReleases()
    {
        return Cache::remember('latest_releases', self::CACHE_DURATION_LONG, function () {
            return App::with(['pricing', 'reviews'])
                ->whereNotNull('release_date')
                ->where('release_date', '<=', now())
                ->orderByDesc('release_date')
                ->limit(self::HOMEPAGE_ITEMS_LIMIT)
                ->get();
        });
    }

    private function getFreeGames()
    {
        return Cache::remember('free_games', self::CACHE_DURATION_LONG, function () {
            return App::with(['reviews'])
                ->where('is_free', true)
                ->whereHas('reviews', fn($q) => $q->where('total_reviews', '>', 100))
                ->orderByDesc('total_recommendations')
                ->limit(self::HOMEPAGE_ITEMS_LIMIT)
                ->get();
        });
    }

    private function getHotDeals()
    {
        return Cache::remember('hot_deals_homepage', self::CACHE_DURATION_SHORT, function () {
            return App::with(['pricing', 'reviews'])
                ->whereHas('pricing', function($q) {
                    $q->where('discount_percent', '>=', 75)
                      ->where('final_price', '>', 0);
                })
                ->join('pricing', 'apps.app_id', '=', 'pricing.app_id')
                ->orderByDesc('pricing.discount_percent')
                ->select('apps.*')
                ->limit(self::HOMEPAGE_ITEMS_LIMIT)
                ->get();
        });
    }

    // ========================================
    // PRIVATE HELPER METHODS - FILTERS
    // ========================================
    
    private function applySearchFilter($query, $search)
    {
        if (!empty(trim($search))) {
            $search = trim($search);
            $query->where(function($q) use ($search) {
                $q->whereRaw("MATCH(name, short_description) AGAINST (? IN NATURAL LANGUAGE MODE)", [$search])
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
    }

    private function applyFreeFilter($query, $free)
    {
        if ($free === '1') {
            $query->where('is_free', true);
        }
    }

    private function applyPriceFilter($query, Request $request)
    {
        if ($request->has('price_min') || $request->has('price_max')) {
            $query->whereHas('pricing', function($q) use ($request) {
                if ($min = $request->input('price_min')) {
                    $q->where('final_price', '>=', $min * 100);
                }
                if ($max = $request->input('price_max')) {
                    $q->where('final_price', '<=', $max * 100);
                }
            });
        }
    }

    private function applyGenreFilter($query, $genre)
    {
        if ($genre) {
            $query->whereHas('genres', fn($q) => $q->where('name', $genre));
        }
    }

    private function applyCategoryFilter($query, $category)
    {
        if ($category) {
            $query->whereHas('categories', fn($q) => $q->where('name', $category));
        }
    }

    private function applyPeriodFilter($query, $period, $dateField = 'release_date')
    {
        switch ($period) {
            case 'week':
                $query->where($dateField, '>=', now()->subWeek());
                break;
            case 'month':
                $query->where($dateField, '>=', now()->subMonth());
                break;
            case '3months':
                $query->where($dateField, '>=', now()->subMonths(3));
                break;
            case '6months':
                $query->where($dateField, '>=', now()->subMonths(6));
                break;
            case 'year':
                $query->where($dateField, '>=', now()->subYear());
                break;
            // 'all' = no filter
        }
    }

    private function applySorting($query, $sort)
    {
        switch ($sort) {
            case 'date':
                $query->orderByDesc('release_date');
                break;
            case 'name':
                $query->orderBy('name');
                break;
            case 'reviews':
                $query->leftJoin('reviews', 'apps.app_id', '=', 'reviews.app_id')
                      ->orderByDesc('reviews.total_reviews')
                      ->select('apps.*');
                break;
            case 'price_asc':
                $query->leftJoin('pricing', 'apps.app_id', '=', 'pricing.app_id')
                      ->orderBy('pricing.final_price')
                      ->select('apps.*');
                break;
            case 'price_desc':
                $query->leftJoin('pricing', 'apps.app_id', '=', 'pricing.app_id')
                      ->orderByDesc('pricing.final_price')
                      ->select('apps.*');
                break;
            default:
                $query->orderByDesc('total_recommendations');
                break;
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS - SEARCH
    // ========================================
    
    private function searchByName($query)
    {
        return App::query()
            ->where('name', 'like', "{$query}%")
            ->orderByDesc('total_recommendations')
            ->limit(self::AUTOCOMPLETE_LIMIT)
            ->get(['app_id', 'name', 'slug']);
    }

    private function searchByHardware($query)
    {
        return App::query()
            ->whereHas('sysRequirements', function($q) use ($query) {
                $q->where(function($subQ) use ($query) {
                    $subQ->where('min_cpu', 'like', "%{$query}%")
                         ->orWhere('rec_cpu', 'like', "%{$query}%")
                         ->orWhere('min_gpu', 'like', "%{$query}%")
                         ->orWhere('rec_gpu', 'like', "%{$query}%")
                         ->orWhere('min_ram', 'like', "%{$query}%")
                         ->orWhere('rec_ram', 'like', "%{$query}%");
                });
            })
            ->orderByDesc('total_recommendations')
            ->limit(self::AUTOCOMPLETE_LIMIT)
            ->get(['app_id', 'name', 'slug']);
    }

    private function isHardwareSearch($search)
    {
        // Check keywords
        foreach (self::HARDWARE_KEYWORDS as $keyword) {
            if (stripos($search, $keyword) !== false) {
                return true;
            }
        }
        
        // Check patterns like "GTX 1060", "RTX 3060", "RX 580"
        if (preg_match('/\b(gtx|rtx|rx|i[3579])\s*\d{3,4}\b/i', $search)) {
            return true;
        }
        
        // Check for RAM patterns like "16 GB"
        if (preg_match('/\d+\s*(gb|mb)/i', $search)) {
            return true;
        }
        
        return false;
    }

    // ========================================
    // PRIVATE HELPER METHODS - GAME DETAIL
    // ========================================
    
    private function getActiveHardware()
    {
        $activeHwId = session('active_hardware_id');
        return $activeHwId ? \App\Models\UserHardware::with(['cpu', 'gpu'])->find($activeHwId) : null;
    }

    /**
     * NEW FEATURE: Get similar games based on genres and categories
     */
    private function getSimilarGames(App $app, $limit = 6)
    {
        return Cache::remember("similar_games_{$app->app_id}", self::CACHE_DURATION_LONG, function () use ($app, $limit) {
            $genreIds = $app->genres->pluck('id');

            if ($genreIds->isEmpty()) {
                return collect();
            }

            // Extract base game name for similarity checking
            $baseName = $this->getBaseGameName($app->name);

            // Similar games:
            // 1. Match by genres (prioritize games with MORE matching genres)
            // 2. Only show games (exclude DLC, software, etc.)
            // 3. Exclude DLC/Edition/Pack keywords
            // 4. Filter out games with similar names (avoid editions/remasters)
            return App::query()
                ->select(
                    'apps.*',
                    DB::raw('COUNT(DISTINCT app_genres.genre_id) as genre_matches')
                )
                ->join('app_genres', 'apps.app_id', '=', 'app_genres.app_id')
                ->whereIn('app_genres.genre_id', $genreIds)
                ->where('apps.app_id', '!=', $app->app_id)
                ->where('apps.type', 'game')  // Only games, no DLC/software
                ->whereNotNull('apps.total_recommendations')
                ->where('apps.total_recommendations', '>', 0)
                // Exclude common DLC/Edition keywords
                ->where('apps.name', 'NOT LIKE', '%DLC%')
                ->where('apps.name', 'NOT LIKE', '%Soundtrack%')
                ->where('apps.name', 'NOT LIKE', '%Pack%')
                ->where('apps.name', 'NOT LIKE', '%Bundle%')
                ->where('apps.name', 'NOT LIKE', '%Season Pass%')
                ->where('apps.name', 'NOT LIKE', '%Expansion%')
                ->where('apps.name', 'NOT LIKE', '%Content Pack%')
                ->groupBy('apps.app_id')
                ->orderByDesc('genre_matches')  // More matching genres = higher priority
                ->orderByDesc('apps.total_recommendations')
                ->limit($limit * 3)  // Get more candidates for filtering
                ->with(['pricing', 'reviews', 'genres'])
                ->get()
                // Post-filter: Remove games with too similar names
                ->filter(function($similar) use ($baseName) {
                    $similarBase = $this->getBaseGameName($similar->name);

                    // Use Levenshtein distance to check name similarity
                    $distance = levenshtein(
                        strtolower(substr($baseName, 0, 255)),
                        strtolower(substr($similarBase, 0, 255))
                    );

                    // Require significant name difference (> 5 characters)
                    return $distance > 5;
                })
                ->take($limit);
        });
    }

    /**
     * Extract base game name by removing edition/DLC markers
     */
    private function getBaseGameName(string $name): string
    {
        // Remove common edition/version suffixes and markers
        $patterns = [
            '/\s*-\s*(Gold|GOTY|Complete|Deluxe|Ultimate|Premium|Standard|Digital|Collector\'?s?|Limited|Enhanced|Definitive|Remastered|Special|Anniversary|Game of the Year).*$/i',
            '/\s*:\s*(Gold|GOTY|Complete|Deluxe|Ultimate|Premium|Standard|Digital|Collector\'?s?|Limited|Enhanced|Definitive|Remastered|Special|Anniversary|Game of the Year).*$/i',
            '/\s*\((Gold|GOTY|Complete|Deluxe|Ultimate|Premium|Standard|Digital|Collector\'?s?|Limited|Enhanced|Definitive|Remastered|Special|Anniversary).*\)$/i',
            '/\s*Edition$/i',
            '/\s*\d{4}$/i', // Remove year at end (e.g., "Game 2024")
            '/\s*\bII\b$/i', // Roman numerals at end
            '/\s*\bIII\b$/i',
            '/\s*\bIV\b$/i',
        ];

        $baseName = $name;
        foreach ($patterns as $pattern) {
            $baseName = preg_replace($pattern, '', $baseName);
        }

        return trim($baseName);
    }

    private function getHardwareOptions()
    {
        // Cache CPU/GPU data for 1 hour to avoid loading on every page load
        $cpus = Cache::remember('hardware_cpus_grouped', 3600, function () {
            return CPU::getAllGrouped();
        });

        $gpus = Cache::remember('hardware_gpus_grouped', 3600, function () {
            return GPU::getAllGrouped();
        });

        // RAM options in GB
        $ramOptions = [
            4 => '4 GB',
            8 => '8 GB',
            12 => '12 GB',
            16 => '16 GB',
            24 => '24 GB',
            32 => '32 GB',
            48 => '48 GB',
            64 => '64 GB',
            96 => '96 GB',
            128 => '128 GB'
        ];

        // Platform options (updated to match new enum values)
        $platformOptions = [
            'windows_10' => 'Windows 10',
            'windows_11' => 'Windows 11',
            'macos' => 'macOS',
            'linux' => 'Linux',
        ];

        return [
            'cpus' => $cpus,
            'gpus' => $gpus,
            'ramOptions' => $ramOptions,
            'platformOptions' => $platformOptions,
        ];
    }

    // ========================================
    // PRIVATE HELPER METHODS - CACHED LISTS
    // ========================================
    
    private function getGenresList()
    {
        return Cache::remember('genres_list', 86400, function () {
            return \App\Models\Genre::orderBy('name')->get(['id', 'name']);
        });
    }

    private function getCategoriesList()
    {
        return Cache::remember('categories_list', 86400, function () {
            return \App\Models\Category::orderBy('name')->limit(20)->get(['id', 'name']);
        });
    }
}