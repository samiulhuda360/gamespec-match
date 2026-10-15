<?php

namespace App\Services\Search;

/** A search request in structured form: filters the database can apply, plus a topic to rank by. */
final class SearchQuery
{
    /** Features a visitor can ask for, and the Steam categories that mean them. */
    public const FEATURES = [
        'single-player' => ['label' => 'Single-player', 'categories' => ['Single-player']],
        'multiplayer' => ['label' => 'Multiplayer', 'categories' => ['Multi-player']],
        'co-op' => ['label' => 'Co-op', 'categories' => ['Co-op', 'Online Co-op', 'Shared/Split Screen Co-op', 'LAN Co-op']],
        'online-co-op' => ['label' => 'Online co-op', 'categories' => ['Online Co-op']],
        'local-co-op' => ['label' => 'Split-screen', 'categories' => ['Shared/Split Screen Co-op', 'Shared/Split Screen', 'Shared/Split Screen PvP']],
        'pvp' => ['label' => 'PvP', 'categories' => ['PvP', 'Online PvP']],
        'controller' => ['label' => 'Controller support', 'categories' => ['Full controller support']],
        'vr' => ['label' => 'VR', 'categories' => ['VR Supported', 'VR Support']],
        'cross-platform' => ['label' => 'Cross-platform', 'categories' => ['Cross-Platform Multiplayer']],
        'mods' => ['label' => 'Mod support', 'categories' => ['Steam Workshop', 'Includes level editor']],
    ];

    public const SORTS = ['relevance', 'popular', 'newest', 'cheapest', 'best-rated'];

    public const RUNS_ON = ['any', 'minimum', 'recommended'];

    /** @var list<string> genre names as stored in the genres table */
    public array $genres = [];

    /** @var list<string> keys of FEATURES */
    public array $features = [];

    public string $topic = '';

    public ?float $maxPrice = null;

    public bool $freeOnly = false;

    public ?int $minReviewPct = null;

    public ?int $releasedAfter = null;

    public ?int $releasedBefore = null;

    public string $runsOn = 'any';

    public ?string $similarTo = null;

    public string $sort = 'relevance';

    public bool $noMicrotransactions = false;

    /** 'rules', 'ai' or 'ai+rules' */
    public string $source = 'rules';

    /** The model was asked but the call failed, so the rules answered. */
    public bool $modelFailed = false;

    public function __construct(public string $text = '')
    {
    }

    /** The fields the eval compares, in a stable shape. */
    public function toArray(): array
    {
        $genres = $this->genres;
        $features = $this->features;
        sort($genres);
        sort($features);

        return [
            'genres' => $genres,
            'features' => $features,
            'max_price' => $this->maxPrice,
            'free_only' => $this->freeOnly,
            'min_review_pct' => $this->minReviewPct,
            'released_after' => $this->releasedAfter,
            'released_before' => $this->releasedBefore,
            'runs_on' => $this->runsOn,
            'similar_to' => $this->similarTo,
            'sort' => $this->sort,
            'no_microtransactions' => $this->noMicrotransactions,
        ];
    }

    /** Short labels for what was understood, shown above the results. */
    public function chips(): array
    {
        $chips = [];
        foreach ($this->genres as $g) {
            $chips[] = $g;
        }
        foreach ($this->features as $f) {
            $chips[] = self::FEATURES[$f]['label'] ?? $f;
        }
        if ($this->freeOnly) {
            $chips[] = 'Free';
        } elseif ($this->maxPrice !== null) {
            $chips[] = 'Under $'.rtrim(rtrim(number_format($this->maxPrice, 2), '0'), '.');
        }
        if ($this->noMicrotransactions) {
            $chips[] = 'No in-app purchases';
        }
        if ($this->minReviewPct) {
            $chips[] = $this->minReviewPct.'%+ positive reviews';
        }
        if ($this->releasedAfter && $this->releasedBefore && $this->releasedAfter === $this->releasedBefore) {
            $chips[] = 'Released in '.$this->releasedAfter;
        } else {
            if ($this->releasedAfter) {
                $chips[] = 'Released '.$this->releasedAfter.' or later';
            }
            if ($this->releasedBefore) {
                $chips[] = 'Released '.$this->releasedBefore.' or earlier';
            }
        }
        if ($this->runsOn !== 'any') {
            $chips[] = $this->runsOn === 'recommended' ? 'Runs well on your PC' : 'Runs on your PC';
        }
        if ($this->similarTo) {
            $chips[] = 'Like '.$this->similarTo;
        }
        if ($this->topic !== '') {
            $chips[] = '“'.$this->topic.'”';
        }
        if ($this->sort !== 'relevance') {
            $chips[] = ['popular' => 'Most popular first', 'newest' => 'Newest first', 'cheapest' => 'Cheapest first', 'best-rated' => 'Best reviewed first'][$this->sort];
        }

        return $chips;
    }
}
