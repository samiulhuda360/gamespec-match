<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Upgrades\UpgradeAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_a_pc_the_visitor_is_sent_to_add_one(): void
    {
        $this->get('/upgrade')->assertRedirect('/hardware/add-specs');
    }

    public function test_advice_counts_games_unlocked_by_each_upgrade(): void
    {
        $pc = $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB');

        $advice = app(UpgradeAdvisor::class)->advise($pc);

        $this->assertSame(153, $advice['games']);
        $this->assertNotEmpty($advice['options']);
        $best = $advice['options'][0];
        $this->assertSame('gpu', $best['component']);
        $this->assertGreaterThan(0, $best['gain_recommended']);
        // Options are sorted by games unlocked at recommended settings.
        $gains = array_column($advice['options'], 'gain_recommended');
        $this->assertSame($gains, collect($gains)->sortDesc()->values()->all());
    }

    public function test_page_shows_advice_and_what_one_game_needs(): void
    {
        $user = User::factory()->create();
        $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB', userId: $user->id);

        $this->actingAs($user)->get('/upgrade?game=cyberpunk-2077')
            ->assertOk()
            ->assertSee('What should I upgrade?')
            ->assertSee('Cyberpunk 2077 at recommended settings')
            ->assertSee('written from the numbers below');
    }

    public function test_a_high_end_pc_gets_no_upgrades(): void
    {
        $pc = $this->pc('AMD Ryzen 7 7800X3D', 'NVIDIA GeForce RTX 4090', 64);

        $this->assertSame([], app(UpgradeAdvisor::class)->advise($pc)['options']);
    }
}
