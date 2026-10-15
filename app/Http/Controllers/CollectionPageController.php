<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Support\Facades\DB;

class CollectionPageController extends Controller
{
    public function show(string $slug)
    {
        $collection = Collection::where('slug', $slug)
            ->where('is_published', 1)
            ->firstOrFail();

        $games = $this->gamesQueryFor($collection)
            ->paginate(24)
            ->withQueryString();

        return view('collections.show', compact('collection', 'games'));
    }

    private function gamesQueryFor(Collection $collection)
    {
        return match ($collection->type) {
            'week'   => $this->queryTrendingWeek($collection),
            'year'   => $this->queryBestOfYear($collection),
            'latest' => $this->queryLatest(),
            default  => $this->queryFallback(),
        };
    }

    /**
     * Trending this week (tighter + more realistic):
     * A game qualifies if:
     *  1) New release in last 7 days
     *     OR
     *  2) Deal updated in last 7 days AND discount >= 60
     *     AND (reviews >= 200 OR recommendations >= 500)
     *
     * This prevents "thousands of games" caused by price rows updating.
     */
    private function queryTrendingWeek(Collection $collection)
    {
        $since = now()->subDays(7);
        $sinceDate = $since->toDateString();
        $todayDate = now()->toDateString();

        // Tunable thresholds (adjust later if you want)
        $minDiscount = 60;       // was 40
        $minReviews  = 200;
        $minRecs     = 500;

        return DB::table('apps as a')
            ->leftJoin('reviews as r', 'r.app_id', '=', 'a.app_id')
            ->leftJoin('pricing as p', function ($join) {
                $join->on('p.app_id', '=', 'a.app_id')
                     ->where('p.currency', '=', 'USD');
            })
            ->where('a.type', 'game')
            ->whereNotNull('a.slug')
            ->where(function ($q) {
                $q->whereNull('a.coming_soon')
                  ->orWhere('a.coming_soon', 0);
            })
            ->where(function ($w) use ($since, $sinceDate, $todayDate, $minDiscount, $minReviews, $minRecs) {

                // Bucket 1: new releases in last 7 days
                $w->whereBetween('a.release_date', [$sinceDate, $todayDate])

                  // Bucket 2: real deals updated this week (tight gate)
                  ->orWhere(function ($d) use ($since, $minDiscount, $minReviews, $minRecs) {
                      $d->where('p.updated_at', '>=', $since)
                        ->where('p.discount_percent', '>=', $minDiscount)
                        ->where(function ($pop) use ($minReviews, $minRecs) {
                            $pop->where('r.total_reviews', '>=', $minReviews)
                                ->orWhere('a.total_recommendations', '>=', $minRecs);
                        });
                  });
            })
            ->select([
                'a.app_id',
                'a.name',
                'a.slug',
                'a.header_image',
                'a.release_date',
                'a.is_free',
                DB::raw('COALESCE(r.total_reviews,0) as total_reviews'),
                DB::raw('COALESCE(a.total_recommendations,0) as total_recommendations'),
                DB::raw('COALESCE(p.discount_percent,0) as discount_percent'),
                DB::raw('p.initial_price as initial_price'),
                DB::raw('p.final_price as final_price'),
                DB::raw("
                    (
                        COALESCE(r.total_reviews,0) * 1.0
                        + COALESCE(a.total_recommendations,0) * 0.25
                        + COALESCE(p.discount_percent,0) * 30
                        + CASE
                            WHEN a.release_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 600
                            ELSE 0
                          END
                    ) as score
                "),
            ])
            ->orderByDesc('score')
            ->orderByDesc('a.release_date');
    }

    /**
     * Best games of a year (example: 2025)
     * Rank by reviews + recommendations (no stat_tracking).
     */
    private function queryBestOfYear(Collection $collection)
    {
        $year = (int) ($collection->year ?: now()->year);
        $from = "{$year}-01-01";
        $to   = "{$year}-12-31";

        return DB::table('apps as a')
            ->leftJoin('reviews as r', 'r.app_id', '=', 'a.app_id')
            ->leftJoin('pricing as p', function ($join) {
                $join->on('p.app_id', '=', 'a.app_id')
                     ->where('p.currency', '=', 'USD');
            })
            ->where('a.type', 'game')
            ->whereNotNull('a.slug')
            ->whereNotNull('a.release_date')
            ->whereBetween('a.release_date', [$from, $to])
            ->where(function ($q) {
                $q->whereNull('a.coming_soon')
                  ->orWhere('a.coming_soon', 0);
            })
            ->select([
                'a.app_id',
                'a.name',
                'a.slug',
                'a.header_image',
                'a.release_date',
                'a.is_free',
                DB::raw('COALESCE(r.total_reviews,0) as total_reviews'),
                DB::raw('COALESCE(a.total_recommendations,0) as total_recommendations'),
                DB::raw('COALESCE(p.discount_percent,0) as discount_percent'),
                DB::raw('p.initial_price as initial_price'),
                DB::raw('p.final_price as final_price'),
                DB::raw("
                    (
                        COALESCE(r.total_reviews,0) * 1.0
                        + COALESCE(a.total_recommendations,0) * 0.30
                    ) as score
                "),
            ])
            ->orderByDesc('score')
            ->orderByDesc('a.release_date');
    }

    private function queryLatest()
    {
        return DB::table('apps as a')
            ->leftJoin('reviews as r', 'r.app_id', '=', 'a.app_id')
            ->where('a.type', 'game')
            ->whereNotNull('a.slug')
            ->whereNotNull('a.release_date')
            ->where(function ($q) {
                $q->whereNull('a.coming_soon')
                  ->orWhere('a.coming_soon', 0);
            })
            ->select([
                'a.app_id','a.name','a.slug','a.header_image','a.release_date','a.is_free',
                DB::raw('COALESCE(r.total_reviews,0) as total_reviews'),
                DB::raw('COALESCE(a.total_recommendations,0) as total_recommendations'),
                DB::raw('0 as discount_percent'),
                DB::raw('NULL as initial_price'),
                DB::raw('NULL as final_price'),
                DB::raw('0 as score'),
            ])
            ->orderByDesc('a.release_date');
    }

    private function queryFallback()
    {
        return DB::table('apps as a')
            ->leftJoin('reviews as r', 'r.app_id', '=', 'a.app_id')
            ->where('a.type', 'game')
            ->whereNotNull('a.slug')
            ->where(function ($q) {
                $q->whereNull('a.coming_soon')
                  ->orWhere('a.coming_soon', 0);
            })
            ->select([
                'a.app_id','a.name','a.slug','a.header_image','a.release_date','a.is_free',
                DB::raw('COALESCE(r.total_reviews,0) as total_reviews'),
                DB::raw('COALESCE(a.total_recommendations,0) as total_recommendations'),
                DB::raw('0 as discount_percent'),
                DB::raw('NULL as initial_price'),
                DB::raw('NULL as final_price'),
                DB::raw('COALESCE(r.total_reviews,0) as score'),
            ])
            ->orderByDesc('score');
    }
}
