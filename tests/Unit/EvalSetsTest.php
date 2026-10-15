<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * The labelled eval sets double as regression tests for the rule-based paths, which are what
 * runs with AI off: their scores must not drop below the rules-only scores in the reports in docs/.
 */
class EvalSetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_spec_rules_keep_their_score_and_never_pick_a_wrong_part(): void
    {
        Artisan::call('ai:eval-specs');
        $out = Artisan::output();

        $this->assertMatchesRegularExpression('/all three right\s*\|\s*(8[5-9]|9\d|100)%/', $out);
        $this->assertMatchesRegularExpression('/wrong part selected\s*\|\s*0\s/', $out);
    }

    public function test_search_rules_keep_their_score(): void
    {
        Artisan::call('ai:eval-search');

        $this->assertMatchesRegularExpression('/all fields right\s*\|\s*(8[3-9]|9\d|100)%/', Artisan::output());
    }
}
