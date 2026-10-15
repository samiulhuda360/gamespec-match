<?php

namespace Tests\Unit;

use App\Models\SysRequirement;
use App\Services\CompatibilityChecker;
use App\Services\Scoring\HardwareCatalog;
use App\Services\Scoring\RequirementScorer;
use App\Services\Scoring\RequirementText;
use App\Services\Scoring\ScoreScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_requirement_html_is_split_into_parts(): void
    {
        $kv = RequirementText::parse('<ul><li><strong>OS:</strong> Windows 10</li><li><strong>Processor:</strong> Intel Core i5-4460 or AMD FX-6300<br></li>'
            .'<li><strong>Memory:</strong> 8 GB RAM</li><li><strong>Graphics:</strong> GTX 960 / RX 470</li><li><strong>Storage:</strong> 50 GB</li></ul>');

        $this->assertSame('Intel Core i5-4460 or AMD FX-6300', $kv['cpu']);
        $this->assertSame('8 GB RAM', $kv['ram']);
        $this->assertSame('GTX 960 / RX 470', $kv['gpu']);
        $this->assertSame('Windows 10', $kv['os']);
    }

    public function test_catalogue_matching_is_whole_word_and_longest_first(): void
    {
        $catalog = app(HardwareCatalog::class);

        $this->assertSame('NVIDIA GeForce GTX 1660 Ti', $catalog->match('gpu', 'GeForce GTX 1660 Ti 6GB')['name']);
        $this->assertSame('NVIDIA GeForce GTX 1660', $catalog->match('gpu', 'GTX 1660')['name']);
        $this->assertSame('AMD Radeon RX 6600 XT', $catalog->match('gpu', 'rx 6600xt')['name']);
        $this->assertNull($catalog->match('gpu', 'RX 5800'), 'rx 580 must not match inside rx 5800');
        $this->assertSame('Intel Core i7-1165G7', $catalog->match('cpu', 'i7-1165G7')['name']);
    }

    public function test_a_requirement_is_as_hard_as_its_weakest_alternative(): void
    {
        $scorer = app(RequirementScorer::class);
        $either = $scorer->component('gpu', 'NVIDIA GeForce GTX 970 or AMD Radeon RX 6600');
        $weak = $scorer->component('gpu', 'NVIDIA GeForce GTX 970');

        $this->assertSame('catalog', $either['method']);
        $this->assertSame($weak['score'], $either['score']);
    }

    public function test_unknown_parts_fall_back_to_vram_and_family(): void
    {
        $scorer = app(RequirementScorer::class);

        $this->assertSame('vram', $scorer->component('gpu', 'Any 4 GB video card')['method']);
        $this->assertSame('family', $scorer->component('cpu', 'Intel Core i5')['method']);
        $this->assertSame(16.0, RequirementScorer::gb('16 GB RAM', 2));
        $this->assertSame(0.5, RequirementScorer::gb('512 MB', 2));
    }

    public function test_spare_ram_does_not_make_up_for_a_slow_gpu(): void
    {
        $req = new SysRequirement([]);
        $req->forceFill(['min_cpu_score' => 50, 'min_gpu_score' => 100, 'min_ram_score' => 8,
            'min_overall_score' => ScoreScale::overall(50, 8, 100), 'rec_cpu_score' => 80, 'rec_gpu_score' => 200,
            'rec_ram_score' => 16, 'rec_overall_score' => ScoreScale::overall(80, 16, 200)]);
        $pc = $this->pc('Intel Core i7-8700', 'NVIDIA GeForce GTX 1060 6GB', 16);
        $more = $this->pc('Intel Core i7-8700', 'NVIDIA GeForce GTX 1060 6GB', 64);
        $checker = app(CompatibilityChecker::class);

        $this->assertSame($checker->verdictFor($pc, $req)['can_run'], $checker->verdictFor($more, $req)['can_run']);
    }
}
