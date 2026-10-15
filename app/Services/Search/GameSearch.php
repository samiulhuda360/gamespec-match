<?php

namespace App\Services\Search;

use App\Ai\Embeddings;
use App\Models\App;
use App\Models\UserHardware;
use App\Services\CompatibilityChecker;
use Illuminate\Support\Collection;

/**
 * Runs a SearchQuery: the database applies the hard filters (genre, features, price, reviews,
 * years), embeddings rank what is left by meaning, and the compatibility checker keeps only
 * games the visitor's PC can run when they asked for that.
 */
class GameSearch
{
    public const LIMIT = 24;

    /** Filtered candidates ranked in PHP. The demo catalogue is small; a full catalogue would rank in a vector index. */
    private const CANDIDATES = 400;

    public function __construct(
        private readonly Embeddings $embeddings,
        private readonly CompatibilityChecker $checker,
    ) {
    }

    /**
     * @return array{games: Collection<int, App>, matches: int, ranking: ?string, notes: list<string>, similar: ?App}
     */
    public function run(SearchQuery $q, ?UserHardware $pc): array
    {
        $notes = [];
        $similar = $q->similarTo ? App::with('genres:id,name')->where('name', $q->similarTo)->first() : null;

        $games = $this->filtered($q, $similar)->limit(self::CANDIDATES)->get();
        if ($games->count() < 3 && $q->genres !== []) {
            // Genre is the filter people state most loosely; widen it before giving up.
            $wider = clone $q;
            $wider->genres = [];
            $games = $this->filtered($wider, $similar)->limit(self::CANDIDATES)->get();
            $notes[] = 'Few games matched '.implode(' + ', $q->genres).', so other genres are included.';
        }

        // Rank by meaning: the topic, and the game the visitor compared to if any.
        $rankText = trim(($similar ? self::text($similar).' ' : '').($q->topic !== '' ? 'Looking for: '.$q->topic : ''));
        $ranking = null;
        $scores = [];
        if ($rankText !== '' && $games->isNotEmpty()) {
            $items = $games->mapWithKeys(fn (App $g) => [$g->app_id => ['type' => 'app', 'id' => $g->app_id, 'text' => self::text($g)]])->all();
            // Game vectors come from games:embed; a request embeds at most a few new games itself.
            $rank = $this->embeddings->rank($rankText, $items, 'search', maxNew: 16);
            $scores = $rank['scores'];
            $ranking = $rank['model'] === Embeddings::LEXICAL ? 'words' : 'meaning';
            if ($ranking === 'words' && max($scores) > 0) {
                // Shared words are all the fallback has: a game sharing none is not a match.
                $games = $games->filter(fn (App $g) => ($scores[$g->app_id] ?? 0) > 0)->values();
            }
        }

        $maxPop = log10(1 + max(1, (int) $games->max('total_recommendations')));
        foreach ($games as $g) {
            $pop = log10(1 + (int) $g->total_recommendations) / $maxPop;
            // Similar games first; among equally similar ones, the better-known game.
            $g->match_score = isset($scores[$g->app_id]) ? $scores[$g->app_id] * (0.85 + 0.15 * $pop) : $pop;
        }

        if ($q->runsOn !== 'any') {
            if ($pc === null) {
                $notes[] = 'Add your PC to keep only the games it can run. Showing every match for now.';
            } else {
                $games = $games->filter(function (App $g) use ($pc, $q) {
                    $g->verdict = $this->checker->checkCompatibility($pc, $g);

                    return $q->runsOn === 'recommended'
                        ? $g->verdict['can_run'] === 'yes_recommended'
                        : in_array($g->verdict['can_run'], ['yes_recommended', 'yes_minimum'], true);
                })->values();
            }
        }

        $games = $this->sorted($games, $q->sort);
        $top = $games->take(self::LIMIT)->values();
        if ($pc !== null) {
            $top->each(fn (App $g) => $g->verdict ??= $this->checker->checkCompatibility($pc, $g));
        }

        return ['games' => $top, 'matches' => $games->count(), 'ranking' => $ranking, 'notes' => $notes, 'similar' => $similar];
    }

    /** What a game is about, for embeddings: name, genres and the store blurb. */
    public static function text(App $g): string
    {
        $genres = $g->relationLoaded('genres') ? $g->genres->pluck('name')->implode(', ') : '';

        return trim($g->name.'. '.($genres !== '' ? 'Genres: '.$genres.'. ' : '').strip_tags((string) $g->short_description));
    }

    private function filtered(SearchQuery $q, ?App $similar)
    {
        $query = App::query()
            ->where('apps.type', 'game')
            ->with(['genres:id,name', 'categories:id,name', 'pricing', 'reviews', 'sysRequirements']);

        foreach ($q->genres as $genre) {
            $query->whereHas('genres', fn ($g) => $g->where('name', $genre));
        }
        foreach ($q->features as $feature) {
            $query->whereHas('categories', fn ($c) => $c->whereIn('name', SearchQuery::FEATURES[$feature]['categories']));
        }
        if ($q->noMicrotransactions) {
            $query->whereDoesntHave('categories', fn ($c) => $c->where('name', 'In-App Purchases'));
        }
        if ($q->freeOnly) {
            $query->where(fn ($w) => $w->where('is_free', true)->orWhereHas('pricing', fn ($p) => $p->where('final_price', 0)));
        } elseif ($q->maxPrice !== null) {
            $cents = (int) round($q->maxPrice * 100);
            $query->where(fn ($w) => $w->where('is_free', true)->orWhereHas('pricing', fn ($p) => $p->where('final_price', '<=', $cents)));
        }
        if ($q->minReviewPct) {
            $query->whereHas('reviews', fn ($r) => $r->where('total_reviews', '>=', 50)
                ->whereRaw('total_positive * 100 >= ? * total_reviews', [$q->minReviewPct]));
        }
        if ($q->releasedAfter) {
            $query->whereYear('release_date', '>=', $q->releasedAfter);
        }
        if ($q->releasedBefore) {
            $query->whereYear('release_date', '<=', $q->releasedBefore);
        }
        if ($similar) {
            $query->where('apps.app_id', '!=', $similar->app_id);
        }

        return $query->orderByDesc('total_recommendations');
    }

    private function sorted(Collection $games, string $sort): Collection
    {
        $sorted = match ($sort) {
            'popular' => $games->sortByDesc('total_recommendations'),
            'newest' => $games->sortByDesc(fn (App $g) => $g->release_date?->timestamp ?? 0),
            'cheapest' => $games->sortBy(fn (App $g) => $g->is_free ? 0 : ($g->pricing->final_price ?? PHP_INT_MAX)),
            // A high score from a handful of reviews should not beat one from thousands.
            'best-rated' => $games->sortByDesc(fn (App $g) => ($g->reviews?->total_reviews ?? 0) >= 50 ? (int) $g->review_percentage : 0),
            default => $games->sortByDesc('match_score'),
        };

        return $sorted->values();
    }
}
