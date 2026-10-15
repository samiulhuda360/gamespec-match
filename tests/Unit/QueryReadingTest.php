<?php

namespace Tests\Unit;

use App\Services\Search\QueryInterpreter;
use App\Services\Search\RuleQueryParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryReadingTest extends TestCase
{
    use RefreshDatabase;

    private function reply(array $override = []): array
    {
        return $override + [
            'topic' => '', 'genres' => [], 'features' => [], 'max_price' => 0, 'free_only' => false, 'min_review_pct' => 0,
            'released_after' => 0, 'released_before' => 0, 'runs_on' => 'any', 'similar_to' => '', 'sort' => 'relevance',
            'no_microtransactions' => false,
        ];
    }

    public function test_rules_read_filters_and_leave_the_rest_as_topic(): void
    {
        $q = app(RuleQueryParser::class)->parse('co-op survival games under $20 my laptop can run');

        $this->assertSame(['co-op'], $q->features);
        $this->assertSame(20.0, $q->maxPrice);
        $this->assertSame('minimum', $q->runsOn);
        $this->assertSame('survival', $q->topic);
    }

    public function test_numbers_that_are_not_prices_are_ignored(): void
    {
        $q = app(RuleQueryParser::class)->parse('base building with up to 8 friends');

        $this->assertNull($q->maxPrice);
    }

    public function test_like_a_title_needs_a_game_we_have(): void
    {
        $parser = app(RuleQueryParser::class);

        $this->assertSame('Hollow Knight', $parser->parse('games like hollow knight')->similarTo);
        $this->assertSame('Slay the Spire', $parser->parse('roguelike deckbuilder like Slay the Spire')->similarTo);
        $this->assertNull($parser->parse('i like war games')->similarTo, 'a word inside a title is not enough after a bare "like"');
    }

    public function test_years_and_review_levels(): void
    {
        $parser = app(RuleQueryParser::class);

        $q = $parser->parse('strategy games released after 2020 with overwhelmingly positive reviews');
        $this->assertSame(['Strategy'], $q->genres);
        $this->assertSame(2021, $q->releasedAfter);
        $this->assertSame(95, $q->minReviewPct);

        $q = $parser->parse('games from 2023');
        $this->assertSame([2023, 2023], [$q->releasedAfter, $q->releasedBefore]);
    }

    public function test_model_values_outside_the_allowed_lists_are_dropped(): void
    {
        $this->fakeAi([$this->reply([
            'genres' => ['RPG', 'Space Opera'], 'features' => ['co-op', 'telepathy'], 'runs_on' => 'always',
            'sort' => 'random', 'max_price' => -5, 'released_after' => 1066, 'similar_to' => 'A Game That Does Not Exist',
        ])]);

        $q = app(QueryInterpreter::class)->interpret('rpg', backfill: false);

        $this->assertSame(['RPG'], $q->genres);
        $this->assertSame(['co-op'], $q->features);
        $this->assertSame('any', $q->runsOn);
        $this->assertSame('relevance', $q->sort);
        $this->assertNull($q->maxPrice);
        $this->assertNull($q->releasedAfter);
        $this->assertNull($q->similarTo);
    }

    public function test_free_to_play_genre_becomes_the_price_filter(): void
    {
        $this->fakeAi([$this->reply(['genres' => ['Action', 'Free To Play']])]);

        $q = app(QueryInterpreter::class)->interpret('free shooters');

        $this->assertSame(['Action'], $q->genres);
        $this->assertTrue($q->freeOnly);
    }

    public function test_empty_model_fields_are_filled_from_the_rules(): void
    {
        $this->fakeAi([$this->reply(['topic' => 'survival'])]);

        $q = app(QueryInterpreter::class)->interpret('survival games under $15 my pc can run');

        $this->assertSame(15.0, $q->maxPrice);
        $this->assertSame('minimum', $q->runsOn);
        $this->assertSame('ai+rules', $q->source);
    }
}
