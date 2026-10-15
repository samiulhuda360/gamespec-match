<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiscoverController extends Controller
{
    const ITEMS_PER_PAGE = 32;
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Show the similar games finder page
     */
    public function similar(Request $request)
    {
        $searchInput = $request->input('game');
        $sourceGame = null;
        $similarGames = collect();
        $genres = $this->getGenresList();

        if ($searchInput) {
            // Try to find game by slug first, then by name
            $sourceGame = App::where('type', 'game')
                ->where(function ($query) use ($searchInput) {
                    $query->where('slug', $searchInput)
                        ->orWhere('name', 'LIKE', $searchInput . '%')
                        ->orWhere('name', 'LIKE', '%' . $searchInput . '%');
                })
                ->with(['genres', 'pricing', 'reviews'])
                ->orderByRaw("CASE
                    WHEN slug = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END", [$searchInput, $searchInput . '%'])
                ->first();

            if ($sourceGame) {
                // Get similar games with filters
                $similarGames = $this->findSimilarGames(
                    $sourceGame,
                    $request->input('genre'),
                    $request->input('platform'),
                    $request->input('price_max'),
                    $request->input('sort', 'similarity')
                );
            }
        }

        return view('discover.similar', [
            'sourceGame' => $sourceGame,
            'similarGames' => $similarGames,
            'genres' => $genres,
            'searchQuery' => $searchInput,
        ]);
    }

    /**
     * Find similar games with filters and sorting
     */
    private function findSimilarGames(App $sourceGame, $genreFilter = null, $platformFilter = null, $priceMax = null, $sort = 'similarity')
    {
        $genreIds = $sourceGame->genres->pluck('id');

        if ($genreIds->isEmpty()) {
            return collect();
        }

        $cacheKey = sprintf(
            'discover_similar_v2_%d_%s_%s_%s_%s',
            $sourceGame->app_id,
            $genreFilter ?? 'all',
            $platformFilter ?? 'all',
            $priceMax ?? 'all',
            $sort
        );

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($sourceGame, $genreIds, $genreFilter, $platformFilter, $priceMax, $sort) {
            $baseName = $this->getBaseGameName($sourceGame->name);

            $query = App::query()
                ->select(
                    'apps.*',
                    DB::raw('COUNT(DISTINCT app_genres.genre_id) as genre_matches')
                )
                ->join('app_genres', 'apps.app_id', '=', 'app_genres.app_id')
                ->whereIn('app_genres.genre_id', $genreIds)
                ->where('apps.app_id', '!=', $sourceGame->app_id)
                ->where('apps.type', 'game')
                ->whereNotNull('apps.total_recommendations')
                ->where('apps.total_recommendations', '>', 0)
                // Exclude DLC/Edition keywords
                ->where('apps.name', 'NOT LIKE', '%DLC%')
                ->where('apps.name', 'NOT LIKE', '%Soundtrack%')
                ->where('apps.name', 'NOT LIKE', '%Pack%')
                ->where('apps.name', 'NOT LIKE', '%Bundle%')
                ->where('apps.name', 'NOT LIKE', '%Season Pass%')
                ->where('apps.name', 'NOT LIKE', '%Expansion%')
                ->where('apps.name', 'NOT LIKE', '%Content Pack%');

            // Apply genre filter
            if ($genreFilter) {
                $query->whereHas('genres', function ($q) use ($genreFilter) {
                    $q->where('genres.id', $genreFilter);
                });
            }

            // Apply platform filter
            if ($platformFilter) {
                $query->whereHas('sysRequirements', function ($q) use ($platformFilter) {
                    $q->where('platform', $platformFilter);
                });
            }

            // Apply price filter
            if ($priceMax) {
                $query->whereHas('pricing', function ($q) use ($priceMax) {
                    $q->where('final_price', '<=', $priceMax * 100); // Convert to cents
                });
            }

            $query->groupBy('apps.app_id');

            // Apply sorting
            switch ($sort) {
                case 'popularity':
                    $query->orderByDesc('apps.total_recommendations');
                    break;
                case 'price_low':
                    $query->leftJoin('pricing', 'apps.app_id', '=', 'pricing.app_id')
                        ->orderBy('pricing.final_price', 'asc');
                    break;
                case 'price_high':
                    $query->leftJoin('pricing', 'apps.app_id', '=', 'pricing.app_id')
                        ->orderBy('pricing.final_price', 'desc');
                    break;
                case 'release_date':
                    $query->orderByDesc('apps.release_date');
                    break;
                case 'similarity':
                default:
                    $query->orderByDesc('genre_matches')
                        ->orderByDesc('apps.total_recommendations');
                    break;
            }

            $results = $query
                ->limit(self::ITEMS_PER_PAGE * 3) // Get more for post-filtering
                ->with(['pricing', 'reviews', 'genres'])
                ->get();

            // Filter out duplicates and games with too similar names
            $seen = [];
            $filtered = $results->filter(function ($similar) use ($baseName, &$seen) {
                $similarBase = $this->getBaseGameName($similar->name);
                $similarBaseLower = strtolower($similarBase);

                // Skip if base name already seen (removes duplicates like "Rainbow Six Siege" variants)
                if (isset($seen[$similarBaseLower])) {
                    return false;
                }

                // Skip if too similar to source game
                $distance = levenshtein(
                    strtolower(substr($baseName, 0, 255)),
                    strtolower(substr($similarBase, 0, 255))
                );

                if ($distance <= 5) {
                    return false;
                }

                // Mark this base name as seen
                $seen[$similarBaseLower] = true;
                return true;
            });

            return $filtered->take(self::ITEMS_PER_PAGE);
        });
    }

    /**
     * Extract base game name by removing edition/DLC markers
     */
    private function getBaseGameName(string $name): string
    {
        $patterns = [
            '/\s*-\s*(Gold|GOTY|Complete|Deluxe|Ultimate|Premium|Standard|Digital|Collector\'?s?|Limited|Enhanced|Definitive|Remastered|Special|Anniversary|Game of the Year).*$/i',
            '/\s*:\s*(Gold|GOTY|Complete|Deluxe|Ultimate|Premium|Standard|Digital|Collector\'?s?|Limited|Enhanced|Definitive|Remastered|Special|Anniversary|Game of the Year).*$/i',
            '/\s*\((Gold|GOTY|Complete|Deluxe|Ultimate|Premium|Standard|Digital|Collector\'?s?|Limited|Enhanced|Definitive|Remastered|Special|Anniversary).*\)$/i',
            '/\s*Edition$/i',
            '/\s*\d{4}$/i',
            '/\s*\bII\b$/i',
            '/\s*\bIII\b$/i',
            '/\s*\bIV\b$/i',
        ];

        $baseName = $name;
        foreach ($patterns as $pattern) {
            $baseName = preg_replace($pattern, '', $baseName);
        }

        return trim($baseName);
    }

    /**
     * Get genres list for filter dropdown
     */
    private function getGenresList()
    {
        return Cache::remember('genres_list', 86400, function () {
            return \App\Models\Genre::orderBy('name')->get();
        });
    }
}
