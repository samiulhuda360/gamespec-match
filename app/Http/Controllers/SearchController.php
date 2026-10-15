<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Genre;
use App\Models\Category;
use App\Http\Requests\AdvancedSearchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    // ========================================
    // ADVANCED SEARCH
    // ========================================
    
    public function advanced(AdvancedSearchRequest $request)
    {
        // Start with base query
        $query = App::query()
            ->with(['reviews', 'pricing' => function($q) {
                $q->where('currency', 'USD'); // Only USD pricing
            }, 'genres', 'categories', 'metrics'])
            ->where('type', 'game'); // Only games, not DLC/software

        // Check if ANY filter is actually filled with a value (not just empty keys)
        $filterKeys = [
            'search', 'genres', 'categories', 'developer', 'publisher',
            'price_min', 'price_max', 'free_only', 'min_review_score',
            'release_from', 'release_to', 'platforms', 'intensity_tier',
            'min_year', 'max_year', 'min_recommendations', 'min_metacritic',
            'controller_support', 'with_achievements', 'family_friendly', 'has_dlc'
        ];

        $hasAnyFilter = collect($filterKeys)->some(function($key) use ($request) {
            return $request->filled($key);
        });

        // ===== TEXT SEARCH =====
        if ($search = $request->validated('search')) {
            $query->where(function($q) use ($search) {
                $q->whereRaw("MATCH(name, short_description) AGAINST (? IN NATURAL LANGUAGE MODE)", [$search])
                  ->orWhere('name', 'like', DB::raw('CONCAT("%", ?, "%")'), [$search]); // Safe parameter binding
            });
        }

        // Store filter data for relevance scoring
        $hasGenres = false;
        $hasCategories = false;
        $genreArray = [];
        $categoryArray = [];

        // ===== GENRES (Multiple) - AND LOGIC =====
        // Games must have ALL selected genres (more restrictive)
        if ($genres = $request->input('genres')) {
            $genreArray = is_array($genres) ? $genres : explode(',', $genres);
            $hasGenres = true;
            // Use multiple whereHas for AND logic (each genre must exist)
            foreach ($genreArray as $genre) {
                $query->whereHas('genres', function($q) use ($genre) {
                    $q->where('name', $genre);
                });
            }
        }

        // ===== CATEGORIES (Multiple) - AND LOGIC =====
        // Games must have ALL selected categories (more restrictive)
        // Categories are MORE IMPORTANT - weighted higher in relevance
        if ($categories = $request->input('categories')) {
            $categoryArray = is_array($categories) ? $categories : explode(',', $categories);
            $hasCategories = true;
            // Use multiple whereHas for AND logic (each category must exist)
            foreach ($categoryArray as $category) {
                $query->whereHas('categories', function($q) use ($category) {
                    $q->where('name', $category);
                });
            }
        }

        // Add relevance scoring when both genres and categories are selected
        // Categories are weighted 2x more important than genres
        if ($hasGenres || $hasCategories) {
            $scoreParts = ['0']; // Base score
            $scoreBindings = [];

            if ($hasCategories) {
                // Add 2 points for each matching category (more important)
                foreach ($categoryArray as $category) {
                    $scoreParts[] = 'COALESCE((SELECT COUNT(*) * 2 FROM app_categories
                                      JOIN categories ON app_categories.category_id = categories.id
                                      WHERE app_categories.app_id = apps.app_id
                                      AND categories.name = ?), 0)';
                    $scoreBindings[] = $category;
                }
            }

            if ($hasGenres) {
                // Add 1 point for each matching genre (less important)
                foreach ($genreArray as $genre) {
                    $scoreParts[] = 'COALESCE((SELECT COUNT(*) FROM app_genres
                                      JOIN genres ON app_genres.genre_id = genres.id
                                      WHERE app_genres.app_id = apps.app_id
                                      AND genres.name = ?), 0)';
                    $scoreBindings[] = $genre;
                }
            }

            // Build the final query that adds all parts together
            $scoreFormula = implode(' + ', $scoreParts);
            $query->selectRaw("apps.*, ($scoreFormula) as relevance_score", $scoreBindings);
        }

        // ===== DEVELOPERS =====
        if ($developer = $request->input('developer')) {
            $query->whereRaw("JSON_CONTAINS(developers, ?)", [json_encode($developer)]);
        }

        // ===== PUBLISHERS =====
        if ($publisher = $request->input('publisher')) {
            $query->whereRaw("JSON_CONTAINS(publishers, ?)", [json_encode($publisher)]);
        }

        // ===== PRICE RANGE =====
        if ($request->has('price_min') || $request->has('price_max')) {
            $query->whereHas('pricing', function($q) use ($request) {
                if ($min = $request->input('price_min')) {
                    $q->where('final_price', '>=', $min * 100); // Convert to cents
                }
                if ($max = $request->input('price_max')) {
                    $q->where('final_price', '<=', $max * 100);
                }
            });
        }

        // ===== FREE GAMES ONLY =====
        if ($request->input('free_only') === '1') {
            $query->where('is_free', true);
        }

        // ===== REVIEW SCORE FILTER =====
        if ($minReviews = $request->input('min_review_score')) {
            $query->whereHas('reviews', function($q) use ($minReviews) {
                $q->whereRaw('(total_positive / NULLIF(total_reviews, 0) * 100) >= ?', [$minReviews])
                  ->where('total_reviews', '>=', 10); // At least 10 reviews for credibility
            });
        }

        // ===== MINIMUM RECOMMENDATIONS =====
        if ($minRecs = $request->input('min_recommendations')) {
            $query->where('total_recommendations', '>=', $minRecs);
        }

        // ===== RELEASE DATE RANGE =====
        if ($fromDate = $request->input('release_from')) {
            $query->where('release_date', '>=', $fromDate);
        }
        if ($toDate = $request->input('release_to')) {
            $query->where('release_date', '<=', $toDate);
        }

        // ===== RELEASED GAMES ONLY (exclude coming soon) =====
        if ($request->input('released_only') === '1') {
            $query->where(function($q) {
                $q->where('coming_soon', false)
                  ->orWhereNull('coming_soon');
            })->whereNotNull('release_date');
        }

        // ===== PLATFORM SUPPORT (Multiple) =====
        if ($platforms = $request->input('platforms')) {
            $platformArray = is_array($platforms) ? $platforms : explode(',', $platforms);
            foreach ($platformArray as $platform) {
                $query->whereHas('sysRequirements', function($q) use ($platform) {
                    $q->where('platform', $platform);
                });
            }
        }

        // ===== HARDWARE INTENSITY TIER =====
        if ($intensityTier = $request->input('intensity_tier')) {
            $query->whereHas('metrics', function($q) use ($intensityTier) {
                $q->where('intensity_tier', $intensityTier);
            });
        }

        // ===== GAME AGE FILTERS (Release Year) =====
        if ($minYear = $request->input('min_year')) {
            $query->whereYear('release_date', '>=', $minYear);
        }
        if ($maxYear = $request->input('max_year')) {
            $query->whereYear('release_date', '<=', $maxYear);
        }

        // ===== MINIMUM REQUIREMENT SCORE (for sorting by spec requirements) =====
        if ($minReqScore = $request->input('min_requirement_score')) {
            $query->whereHas('metrics', function($q) use ($minReqScore) {
                $q->where('min_requirement_score', '>=', $minReqScore);
            });
        }
        if ($maxReqScore = $request->input('max_requirement_score')) {
            $query->whereHas('metrics', function($q) use ($maxReqScore) {
                $q->where('min_requirement_score', '<=', $maxReqScore);
            });
        }

        // ===== METACRITIC SCORE =====
        if ($minMetacritic = $request->input('min_metacritic')) {
            $query->where('metacritic_score', '>=', $minMetacritic);
        }

        // ===== CONTROLLER SUPPORT =====
        if ($controllerSupport = $request->input('controller_support')) {
            if ($controllerSupport === 'any') {
                $query->whereIn('controller_support', ['partial', 'full']);
            } else {
                $query->where('controller_support', $controllerSupport);
            }
        }

        // ===== WITH ACHIEVEMENTS =====
        if ($request->input('with_achievements') === '1') {
            $query->where('total_achievements', '>', 0);
        }

        // ===== FAMILY FRIENDLY (No Adult Content) =====
        if ($request->input('family_friendly') === '1') {
            $query->where('has_adult_content', false);
        }

        // ===== HAS DLC =====
        if ($request->input('has_dlc') === '1') {
            $query->where('dlc_count', '>', 0);
        }

        // ===== SORTING =====
        $sort = $request->input('sort', 'recommendations_desc');
        $this->applySorting($query, $sort);

        // Execute query with pagination ONLY if filters are applied
        if ($hasAnyFilter) {
            $apps = $query->paginate(30)->withQueryString();
        } else {
            // No filters applied - return empty collection
            $apps = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 30);
        }

        // Get filter options (cached for 1 hour)
        $filterOptions = $this->getFilterOptions();

        return view('search.advanced', array_merge(
            compact('apps'),
            $filterOptions
        ));
    }

    // ========================================
    // DEALS PAGE
    // ========================================
    
    public function deals(Request $request)
    {
        $minDiscount = $request->input('min_discount', 10);
        $maxPrice = $request->input('max_price');
        $genre = $request->input('genre');
        $sort = $request->input('sort', 'discount_desc');

        $query = App::query()
            ->with(['reviews', 'pricing' => function($q) {
                $q->where('currency', 'USD');
            }, 'genres'])
            ->where('type', 'game')
            ->whereHas('pricing', function($q) use ($minDiscount, $maxPrice) {
                $q->where('currency', 'USD')
                  ->where('discount_percent', '>=', $minDiscount)
                  ->where('final_price', '>', 0); // Must have a price (not free)
                
                if ($maxPrice) {
                    $q->where('final_price', '<=', $maxPrice * 100);
                }
            });

        // Genre filter
        if ($genre) {
            $query->whereHas('genres', fn($q) => $q->where('name', $genre));
        }

        // Only released games
        $query->where(function($q) {
            $q->where('coming_soon', false)
              ->orWhereNull('coming_soon');
        });

        // Sorting
        switch ($sort) {
            case 'discount_desc':
                $query->join('pricing', 'apps.app_id', '=', 'pricing.app_id')
                      ->where('pricing.currency', 'USD')
                      ->orderByDesc('pricing.discount_percent')
                      ->select('apps.*');
                break;
                
            case 'price_asc':
                $query->join('pricing', 'apps.app_id', '=', 'pricing.app_id')
                      ->where('pricing.currency', 'USD')
                      ->orderBy('pricing.final_price')
                      ->select('apps.*');
                break;
                
            case 'savings_desc':
                $query->join('pricing', 'apps.app_id', '=', 'pricing.app_id')
                      ->where('pricing.currency', 'USD')
                      ->orderByRaw('(pricing.initial_price - pricing.final_price) DESC')
                      ->select('apps.*');
                break;
                
            case 'reviews_desc':
                $query->leftJoin('reviews', 'apps.app_id', '=', 'reviews.app_id')
                      ->orderByDesc('reviews.total_reviews')
                      ->select('apps.*');
                break;
                
            default:
                $query->orderByDesc('total_recommendations');
                break;
        }

        $apps = $query->paginate(30)->withQueryString();
        
        // Get genres (cached)
        $genres = Cache::remember('genres_list', 86400, function () {
            return Genre::orderBy('name')->get(['id', 'name']);
        });

        // Calculate total savings for stats
        $totalSavings = $this->calculateTotalSavings();

        return view('games.deals', compact('apps', 'genres', 'totalSavings'));
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================
    
    /**
     * Apply sorting to query
     */
    private function applySorting($query, $sort)
    {
        switch ($sort) {
            case 'price_asc':
                $query->leftJoin('pricing', function($join) {
                        $join->on('apps.app_id', '=', 'pricing.app_id')
                             ->where('pricing.currency', '=', 'USD');
                    })
                    ->orderBy('pricing.final_price', 'asc')
                    ->select('apps.*');
                break;

            case 'price_desc':
                $query->leftJoin('pricing', function($join) {
                        $join->on('apps.app_id', '=', 'pricing.app_id')
                             ->where('pricing.currency', '=', 'USD');
                    })
                    ->orderBy('pricing.final_price', 'desc')
                    ->select('apps.*');
                break;

            case 'date_desc':
                $query->orderByDesc('release_date');
                break;

            case 'date_asc':
                $query->orderBy('release_date');
                break;

            case 'name_asc':
                $query->orderBy('name');
                break;

            case 'name_desc':
                $query->orderByDesc('name');
                break;

            case 'reviews_desc':
                $query->leftJoin('reviews', 'apps.app_id', '=', 'reviews.app_id')
                      ->orderByDesc('reviews.total_reviews')
                      ->select('apps.*');
                break;

            case 'rating_desc':
                $query->leftJoin('reviews', 'apps.app_id', '=', 'reviews.app_id')
                      ->whereNotNull('reviews.total_reviews')
                      ->where('reviews.total_reviews', '>=', 10)
                      ->orderByRaw('(reviews.total_positive / NULLIF(reviews.total_reviews, 0)) DESC')
                      ->select('apps.*');
                break;

            case 'metacritic_desc':
                $query->whereNotNull('metacritic_score')
                      ->where('metacritic_score', '>', 0)
                      ->orderByDesc('metacritic_score');
                break;

            case 'achievements_desc':
                $query->where('total_achievements', '>', 0)
                      ->orderByDesc('total_achievements');
                break;

            // ===== NEW: HARDWARE REQUIREMENT SORTING =====
            case 'low_end_first':
                // Sort by lowest system requirements (light games first)
                $query->leftJoin('game_metrics', 'apps.app_id', '=', 'game_metrics.app_id')
                      ->orderBy('game_metrics.min_requirement_score', 'asc')
                      ->select('apps.*');
                break;

            case 'high_end_first':
                // Sort by highest system requirements (demanding games first)
                $query->leftJoin('game_metrics', 'apps.app_id', '=', 'game_metrics.app_id')
                      ->orderByDesc('game_metrics.rec_requirement_score')
                      ->select('apps.*');
                break;

            case 'popularity_desc':
                // Sort by popularity score from game_metrics
                $query->leftJoin('game_metrics', 'apps.app_id', '=', 'game_metrics.app_id')
                      ->orderByDesc('game_metrics.popularity_score')
                      ->select('apps.*');
                break;

            case 'recommendations_desc':
            default:
                $query->orderByDesc('total_recommendations');
                break;
        }
    }

    /**
     * Get all filter options (cached)
     */
    private function getFilterOptions()
    {
        return Cache::remember('search_filter_options', 3600, function () {
            // Get all genres
            $allGenres = Genre::orderBy('name')->get(['id', 'name']);

            // Get all categories
            $allCategories = Category::orderBy('name')->get(['id', 'name']);

            return [
                'allGenres' => $allGenres,
                'allCategories' => $allCategories,
            ];
        });
    }

    /**
     * Calculate total savings from deals
     */
    private function calculateTotalSavings()
    {
        return Cache::remember('total_savings_stats', 1800, function () {
            $result = DB::table('pricing')
                ->where('currency', 'USD')
                ->where('discount_percent', '>', 0)
                ->selectRaw('SUM(initial_price - final_price) as total_savings')
                ->first();
            
            return $result->total_savings ?? 0;
        });
    }

    // ========================================
    // NEW FEATURE: AUTOCOMPLETE SEARCH
    // ========================================
    
    /**
     * Autocomplete search endpoint
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = App::where('name', 'like', "{$query}%")
            ->orWhereRaw("MATCH(name) AGAINST (? IN NATURAL LANGUAGE MODE)", [$query])
            ->orderByDesc('total_recommendations')
            ->limit(10)
            ->get(['app_id', 'name', 'slug', 'header_image'])
            ->map(function($app) {
                return [
                    'id' => $app->app_id,
                    'name' => $app->name,
                    'url' => route('apps.show', $app->slug),
                    'image' => $app->header_image,
                ];
            });

        return response()->json($results);
    }

    // ========================================
    // NEW FEATURE: SIMILAR GAMES SEARCH
    // ========================================
    
    /**
     * Find similar games based on genres and categories
     */
    public function similar(Request $request, $appId)
    {
        $app = App::with('genres', 'categories')->findOrFail($appId);
        
        $genreIds = $app->genres->pluck('id');
        $categoryIds = $app->categories->pluck('id');
        
        if ($genreIds->isEmpty()) {
            return response()->json([]);
        }

        // Find games with matching genres
        $similarGames = App::query()
            ->with(['pricing', 'reviews'])
            ->where('app_id', '!=', $appId)
            ->where('type', 'game')
            ->whereHas('genres', function($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            })
            // Optionally also match categories
            ->when($categoryIds->isNotEmpty(), function($q) use ($categoryIds) {
                $q->whereHas('categories', function($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            })
            ->orderByDesc('total_recommendations')
            ->limit(12)
            ->get();

        return response()->json($similarGames);
    }

    // ========================================
    // NEW FEATURE: TRENDING SEARCH TERMS
    // ========================================
    
    /**
     * Get trending search terms (you'll need to track searches first)
     */
    public function trending()
    {
        // This would require a search_logs table to track what users search for
        // For now, return popular genres and categories
        
        $trending = Cache::remember('trending_search_terms', 3600, function () {
            return [
                'genres' => Genre::orderByDesc('name')
                    ->limit(10)
                    ->pluck('name'),
                'popular_games' => App::orderByDesc('total_views')
                    ->limit(10)
                    ->pluck('name'),
            ];
        });

        return response()->json($trending);
    }
}