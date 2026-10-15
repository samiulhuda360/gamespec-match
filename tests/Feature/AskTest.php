<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AskTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_read_the_request_when_ai_is_off(): void
    {
        $this->get('/ask?q='.urlencode('co-op games under $20'))
            ->assertOk()
            ->assertSee('Under $20')
            ->assertSee('Co-op')
            ->assertSee('read by rules');
    }

    public function test_filters_are_applied_to_the_results(): void
    {
        $html = $this->get('/ask?q='.urlencode('free multiplayer games'))->assertOk()->getContent();

        $this->assertStringContainsString('Counter-Strike 2', $html);
        $this->assertStringNotContainsString('>Cyberpunk 2077<', $html); // paid, single-player
    }

    public function test_the_model_reading_is_used_and_shown(): void
    {
        $this->fakeAi([[
            'topic' => 'survival crafting', 'genres' => [], 'features' => ['co-op'], 'max_price' => 20, 'free_only' => false,
            'min_review_pct' => 0, 'released_after' => 0, 'released_before' => 0, 'runs_on' => 'any', 'similar_to' => '',
            'sort' => 'relevance', 'no_microtransactions' => false,
        ]], fn (array $inputs) => array_map(fn ($t) => str_contains(strtolower($t), 'surviv') ? [1.0, 0.1] : [0.1, 1.0], $inputs));
        $this->artisan('games:embed', ['--pause' => 0])->assertSuccessful();

        $this->get('/ask?q='.urlencode('something to survive in with mates, max twenty bucks'))
            ->assertOk()
            ->assertSee('read by AI')
            ->assertSee('Under $20')
            ->assertSee('closest in meaning first');

        $this->assertSame(1, DB::table('ai_calls')->where('feature', 'search-query')->count());
    }

    public function test_requests_are_rate_limited(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->get('/ask?q=rpg')->assertOk();
        }
        $this->get('/ask?q=rpg')->assertStatus(429);
    }
}
