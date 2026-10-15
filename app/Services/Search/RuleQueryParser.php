<?php

namespace App\Services\Search;

use App\Models\App;
use App\Models\Genre;

/**
 * Reads a search request with patterns: prices, "free", genres, features (co-op, split-screen,
 * controller...), review level, release years, "my PC can run", "games like X" and sort words.
 * What is left over becomes the topic the results are ranked by.
 *
 * It runs when AI is off, and fills any field the model leaves empty when AI is on.
 */
class RuleQueryParser
{
    /** Words people use for each Steam genre. */
    private const GENRE_WORDS = [
        'Action' => 'action|shooters?|fps|hack and slash|beat ?em up',
        'Adventure' => 'adventure|exploration',
        'RPG' => 'rpgs?|role[- ]?playing|jrpgs?|arpgs?',
        'Strategy' => 'strategy|rts|4x|tactics|tactical|city builders?|tower defen[cs]e',
        'Simulation' => 'simulation|simulators?|sims?',
        'Indie' => 'indie',
        'Casual' => 'casual',
        'Racing' => 'racing|racers?|driving',
        'Sports' => 'sports?|football|soccer|basketball|golf',
        'Massively Multiplayer' => 'mmos?|mmorpgs?|massively multiplayer',
        'Early Access' => 'early access',
    ];

    private const FEATURE_WORDS = [
        'online-co-op' => 'online co-?op',
        'local-co-op' => 'split[- ]?screen|couch co-?op|local co-?op|local multi-?player|same couch|same screen',
        'co-op' => 'co-?op|cooperative|with (?:a |my )?friends?|with my (?:partner|girlfriend|boyfriend|wife|husband|kids?|brother|sister)',
        'multiplayer' => 'multi-?player|online multiplayer',
        'single-player' => 'single[- ]?player|solo|offline',
        'pvp' => 'pvp|competitive|versus',
        'controller' => '(?:controller|gamepad|joypad)s?(?: support)?',
        'vr' => '(?:vr|virtual reality)(?: support)?',
        'cross-platform' => 'cross[- ]?(?:platform|play)|crossplay',
        'mods' => '(?:mods?|modding)(?: support)?|workshop|level editor',
    ];

    private const FILLER = [
        'a', 'an', 'the', 'some', 'any', 'game', 'games', 'title', 'titles', 'find', 'show', 'me', 'i', 'want', 'wanna',
        'looking', 'for', 'to', 'play', 'that', 'which', 'with', 'and', 'or', 'on', 'in', 'of', 'is', 'are', 'my', 'good',
        'great', 'recommend', 'suggest', 'something', 'please', 'can', 'could', 'you', 'get', 'list', 'what', 'best',
        'top', 'fun', 'cool', 'nice', 'new', 'old', 'it', 'be', 'at', 'by', 'from', 'like', 'similar', 'but', 'also',
        'really', 'very', 'pc', 'steam', 'run', 'runs', 'will', 'would', 'dollars', 'bucks', 'usd', 'price', 'cheap',
        'cheaper', 'cheapest', 'budget', 'popular', 'free', 'under', 'below', 'less', 'than', 'released', 'after',
        'before', 'since', 'newer', 'older', 'recent', 'latest', 'newest', 'support', 'rated', 'reviews', 'reviewed',
        'sale', 'there', 'where', 'all', 'every',
    ];

    public function parse(string $text): SearchQuery
    {
        $q = new SearchQuery($text);
        $t = ' '.mb_strtolower(trim($text)).' ';
        // Match a phrase, remove every occurrence of it from what is left, return the first match.
        $take = function (string $pattern) use (&$t): ?array {
            if (! preg_match($pattern, $t, $m)) {
                return null;
            }
            $t = preg_replace($pattern, ' ', $t);

            return $m;
        };

        // "games like Hollow Knight", "similar to Stardew Valley", "a racing sim like Assetto Corsa"
        if ($m = $take('/\b(games? like|something like|anything like|stuff like|similar to|in the style of|like)\s+(.+?)(?=\s+(?:but|that|with|under|for|on|which|and|i can|my)\b|[,.!?]|\s*$)/')) {
            // After a bare "like" ("I like shooters") only a whole title counts, not a word inside one.
            $title = $this->findTitle($m[2], partial: $m[1] !== 'like');
            if ($title !== null) {
                $q->similarTo = $title;
            } else {
                $t .= ' '.$m[2].' '; // not a game we know: keep the words as the topic
            }
        }

        // "under 20" is a price; "up to 8 friends" is not, so the weaker words need a currency.
        if ($m = $take('/\b(?:under|below|less than|cheaper than)\s*\$?\s*(\d+(?:\.\d+)?)(?!\s*(?:players?|friends?|people|hours?|gb|years?|fps|%))\s*(?:\$|dollars?|bucks|usd)?/')) {
            $q->maxPrice = (float) $m[1];
        } elseif ($m = $take('/\b(?:max(?:imum)?|up to|at most|no more than)\s*(?:\$\s*(\d+(?:\.\d+)?)|(\d+(?:\.\d+)?)\s*(?:\$|dollars?|bucks|usd))/')) {
            $q->maxPrice = (float) ($m[1] !== '' ? $m[1] : $m[2]);
        } elseif ($m = $take('/\$\s*(\d+(?:\.\d+)?)\s*(?:or less|or under|max)\b/')) {
            $q->maxPrice = (float) $m[1];
        }
        if ($take('/\b(?:free[- ]to[- ]play|f2p)\b|\bfree\b(?![- ]roam)/')) {
            $q->freeOnly = true;
        }
        if ($take('/\b(?:no|without|zero|free of)\s+(?:micro-?transactions|mtx|in-?app purchases|pay[- ]to[- ]win|p2w)\b/')) {
            $q->noMicrotransactions = true;
        }

        if ($take('/\boverwhelmingly positive\b/')) {
            $q->minReviewPct = 95;
        } elseif ($take('/\b(?:very positive|well[- ]reviewed|highly rated|best[- ]reviewed|good reviews|great reviews|top[- ]rated|critically acclaimed)\b/')) {
            $q->minReviewPct = 80;
        } elseif ($take('/\bmostly positive\b/')) {
            $q->minReviewPct = 70;
        }

        $year = (int) now()->year;
        if ($m = $take('/\b(?:released |came out |from |in )?(?:after|since|newer than)\s+((?:19|20)\d{2})\b/')) {
            $q->releasedAfter = (int) $m[1] + (str_contains($m[0], 'after') || str_contains($m[0], 'newer') ? 1 : 0);
        }
        if ($m = $take('/\b(?:released |came out )?(?:before|older than)\s+((?:19|20)\d{2})\b/')) {
            $q->releasedBefore = (int) $m[1] - 1;
        }
        if ($m = $take('/\b(?:released in|from|in|of)\s+((?:19|20)\d{2})\b/')) {
            $q->releasedAfter = $q->releasedBefore = (int) $m[1];
        }
        if ($take('/\b(?:this year)\b/')) {
            $q->releasedAfter = $q->releasedBefore = $year;
        } elseif ($take('/\b(?:last year)\b/')) {
            $q->releasedAfter = $q->releasedBefore = $year - 1;
        } elseif ($q->releasedAfter === null && $q->releasedBefore === null && ($m = $take('/\b((?:19|20)\d{2})\b/'))) {
            $q->releasedAfter = $q->releasedBefore = (int) $m[1]; // "2023 rpgs"
        }

        if ($take('/\b(?:(?:at|on) (?:high|max(?:imum)?|ultra|recommended) settings|smoothly|run(?:s)? well|without lag)\b/')) {
            $q->runsOn = 'recommended';
        }
        if ($take('/\b(?:(?:my|this|our) (?:pc|computer|laptop|rig|machine|setup|potato)(?: can (?:run|handle))?|i can run|can run|(?:that |which )?runs? on|will run|able to run|playable on)\b/')) {
            $q->runsOn = $q->runsOn === 'any' ? 'minimum' : $q->runsOn;
        }

        foreach (self::FEATURE_WORDS as $feature => $words) {
            if ($take('/\b(?:'.$words.')\b/')) {
                $q->features[] = $feature;
            }
        }
        if (in_array('online-co-op', $q->features, true) || in_array('local-co-op', $q->features, true)) {
            $q->features = array_values(array_diff($q->features, ['co-op']));
        }

        // Genre words stay in the topic as well: "survival RPG" ranks by both words.
        $known = $this->genreNames();
        foreach (self::GENRE_WORDS as $genre => $words) {
            if (in_array($genre, $known, true) && preg_match('/\b(?:'.$words.')\b/', $t)) {
                $q->genres[] = $genre;
            }
        }

        $sortWords = [
            'popular' => '/\b(?:most popular|popular|most played|trending)\b/',
            'newest' => '/\b(?:newest|latest|new releases?|recent(?:ly released)?|new)\b/',
            'cheapest' => '/\b(?:cheapest|cheap|budget|inexpensive)\b/',
            'best-rated' => '/\b(?:best[- ]rated|highest[- ]rated|best reviewed|top[- ]rated)\b/',
        ];
        foreach ($sortWords as $sort => $pattern) {
            if (preg_match($pattern, $text === '' ? '' : mb_strtolower($text))) {
                $q->sort = $sort;
                break;
            }
        }

        $words = preg_split('/[^\p{L}\p{N}\-]+/u', $t, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $q->topic = trim(implode(' ', array_filter($words, fn ($w) => ! in_array($w, self::FILLER, true) && mb_strlen($w) > 1)));

        return $q;
    }

    /** @return list<string> */
    public function genreNames(): array
    {
        return once(fn () => Genre::query()->orderBy('name')->pluck('name')->all());
    }

    /**
     * A game title we have, from what the visitor typed after "like": the exact title, a title
     * that starts with it ("the witcher 3" finds "The Witcher 3: Wild Hunt"), or with $partial
     * any title containing it.
     */
    public function findTitle(string $typed, bool $partial = true): ?string
    {
        $typed = trim(preg_replace('/\s+/', ' ', $typed), " \t\"'“”");
        if (mb_strlen($typed) < 3) {
            return null;
        }
        $escaped = addcslashes($typed, '%_\\');
        $byPopularity = fn ($q) => $q->orderByDesc('total_recommendations')->value('name');

        return App::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($typed)])->value('name')
            ?? $byPopularity(App::query()->where(fn ($w) => $w->where('name', 'like', $escaped.' %')->orWhere('name', 'like', $escaped.':%')))
            ?? ($partial ? $byPopularity(App::query()->where('name', 'like', '%'.$escaped.'%')) : null);
    }
}
