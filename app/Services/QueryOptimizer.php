<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Query Optimizer Service
 *
 * Provides optimized database queries for large datasets
 */
class QueryOptimizer
{
    /**
     * Get apps by genre IDs using optimized JOIN
     *
     * Much faster than whereHas() for large datasets
     */
    public static function getAppsByGenres(array $genreIds, int $limit = 10, ?int $excludeAppId = null)
    {
        $query = DB::table('apps')
            ->select('apps.*', DB::raw('COUNT(DISTINCT app_genres.genre_id) as genre_matches'))
            ->join('app_genres', 'apps.app_id', '=', 'app_genres.app_id')
            ->whereIn('app_genres.genre_id', $genreIds)
            ->whereNotNull('apps.total_recommendations')
            ->groupBy('apps.app_id');

        if ($excludeAppId) {
            $query->where('apps.app_id', '!=', $excludeAppId);
        }

        return $query
            ->orderByDesc('genre_matches')  // Prioritize games with more matching genres
            ->orderByDesc('apps.total_recommendations')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular games from last N days using indexed query
     */
    public static function getPopularGameIds(int $days = 30, int $limit = 12)
    {
        return Cache::remember("popular_game_ids_{$days}d", 1800, function () use ($days, $limit) {
            return DB::table('stats_tracking')
                ->select('app_id', DB::raw('COUNT(*) as view_count'))
                ->where('stat_type', 'game_view')
                ->where('created_at', '>=', now()->subDays($days))
                ->whereNotNull('app_id')
                ->groupBy('app_id')
                ->orderByDesc('view_count')
                ->limit($limit)
                ->pluck('app_id');
        });
    }

    /**
     * Get deals with optimized query
     */
    public static function getDeals(int $minDiscountPercent = 75, int $limit = 8)
    {
        return DB::table('apps')
            ->select('apps.*', 'pricing.discount_percent', 'pricing.final_price')
            ->join('pricing', 'apps.app_id', '=', 'pricing.app_id')
            ->where('pricing.discount_percent', '>=', $minDiscountPercent)
            ->whereNotNull('pricing.final_price')
            ->where('pricing.final_price', '>', 0)
            ->orderByDesc('pricing.discount_percent')
            ->orderByDesc('apps.total_recommendations')
            ->limit($limit)
            ->get();
    }

    /**
     * Optimize database tables (run via command or cron)
     */
    public static function optimizeTables()
    {
        $tables = [
            'apps',
            'app_genres',
            'app_categories',
            'stats_tracking',
            'pricing',
            'reviews',
            'sys_requirements'
        ];

        foreach ($tables as $table) {
            DB::statement("OPTIMIZE TABLE {$table}");
        }
    }

    /**
     * Get table statistics for monitoring
     */
    public static function getTableStats()
    {
        return [
            'apps_count' => DB::table('apps')->count(),
            'genres_count' => DB::table('genres')->count(),
            'categories_count' => DB::table('categories')->count(),
            'stats_count' => DB::table('stats_tracking')->count(),
            'cache_size' => Cache::getStore() instanceof \Illuminate\Cache\FileStore
                ? self::getFileCacheSize()
                : 'N/A',
        ];
    }

    private static function getFileCacheSize()
    {
        $cachePath = storage_path('framework/cache/data');
        if (!is_dir($cachePath)) {
            return '0 MB';
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cachePath)) as $file) {
            $size += $file->getSize();
        }

        return round($size / 1024 / 1024, 2) . ' MB';
    }
}
