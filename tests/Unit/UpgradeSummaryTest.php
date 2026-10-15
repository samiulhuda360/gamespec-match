<?php

namespace Tests\Unit;

use App\Services\Upgrades\UpgradeAdvisor;
use App\Services\Upgrades\UpgradeSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpgradeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_written_as_words_are_checked_too(): void
    {
        $facts = 'Upgrade to RTX 4060: 43 more games, 3 playable';

        $this->assertTrue(UpgradeSummary::grounded('It adds 43 games and makes 3 playable.', $facts));
        $this->assertTrue(UpgradeSummary::grounded('It adds forty-three games.', $facts));
        $this->assertFalse(UpgradeSummary::grounded('It adds forty games.', $facts));
        $this->assertFalse(UpgradeSummary::grounded('Expect 60 fps.', $facts));
    }

    public function test_a_draft_with_an_invented_number_is_replaced_by_the_template(): void
    {
        $pc = $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB');
        $advice = app(UpgradeAdvisor::class)->advise($pc);
        $this->fakeAi(['Buy an RTX 3060 and you will get 144 fps in every game.']);

        $out = app(UpgradeSummary::class)->write($pc, $advice);

        $this->assertSame('rules', $out['source']);
        $this->assertStringContainsString("runs {$advice['baseline']['recommended']} of 153 games", $out['text']);
    }

    public function test_a_grounded_draft_is_used(): void
    {
        $pc = $this->pc('Intel Core i5-4460', 'NVIDIA GeForce GTX 1060 6GB');
        $advice = app(UpgradeAdvisor::class)->advise($pc);
        $best = $advice['options'][0];
        $this->fakeAi(["The best single upgrade is the {$best['part']}, which lets {$best['gain_recommended']} more games run at recommended settings."]);

        $out = app(UpgradeSummary::class)->write($pc, $advice);

        $this->assertSame('ai', $out['source']);
    }
}
