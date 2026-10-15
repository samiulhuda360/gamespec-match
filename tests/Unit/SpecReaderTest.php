<?php

namespace Tests\Unit;

use App\Services\Specs\RuleSpecParser;
use App\Services\Specs\SpecReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecReaderTest extends TestCase
{
    use RefreshDatabase;

    private function reply(array $override = []): array
    {
        return $override + ['cpu' => '', 'gpu' => '', 'ram_gb' => 0, 'os' => '', 'laptop' => false];
    }

    public function test_rules_read_parts_ram_os_and_laptops(): void
    {
        $r = app(SpecReader::class)->read('Lenovo Legion 5, Ryzen 7 5800H, RTX 3060, 16GB, Win 11');

        $this->assertSame('AMD Ryzen 7 5800H', $r->cpu['name']);
        $this->assertSame('NVIDIA GeForce RTX 3060 Laptop GPU', $r->gpu['name'], 'a laptop gets the laptop part');
        $this->assertSame(16, $r->ramGb);
        $this->assertSame('windows_11', $r->platform);
        $this->assertSame('rules', $r->source);
    }

    public function test_video_memory_and_storage_are_not_read_as_ram(): void
    {
        $this->assertSame(16, RuleSpecParser::ram('rx 580 8gb, i5 9400f, 16gb'));
        $this->assertSame(32, RuleSpecParser::ram('ryzen 7 | rx 6800 xt | 32gb'));
        $this->assertSame(16, RuleSpecParser::ram('memory: 16384mb ram'));
        $this->assertSame(16, RuleSpecParser::ram('installed physical memory (ram) 16.0 gb'));
        $this->assertNull(RuleSpecParser::ram('gtx 1060 6gb with a 1tb ssd'));
    }

    public function test_the_model_is_only_asked_for_missing_parts(): void
    {
        $this->fakeAi([$this->reply(['gpu' => 'NVIDIA GeForce GTX 1660 SUPER'])]);

        $r = app(SpecReader::class)->read('ryzen 3600 / 1660 super / 16gb');

        $this->assertSame('AMD Ryzen 5 3600', $r->cpu['name']);
        $this->assertSame('text', $r->how['cpu']);
        $this->assertSame('NVIDIA GeForce GTX 1660 Super', $r->gpu['name']);
        $this->assertSame('ai', $r->how['gpu']);
        $this->assertSame('rules+ai', $r->source);
    }

    public function test_no_model_call_when_the_rules_found_everything(): void
    {
        $this->fakeAi([]);

        $r = app(SpecReader::class)->read('i5 4460, gtx 1060 6gb, 16gb ram');

        $this->assertSame('rules', $r->source);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_a_part_whose_model_number_is_not_in_the_text_is_rejected(): void
    {
        // "i5 4th gen" could be several CPUs: a confident guess must not be selected.
        $this->fakeAi([$this->reply(['cpu' => 'Intel Core i5-4460'])]);

        $r = app(SpecReader::class)->read('old pc with an i5 4th gen and a gtx 750 ti, 8gb');

        $this->assertNull($r->cpu);
        $this->assertSame('NVIDIA GeForce GTX 750 Ti', $r->gpu['name']);
        $this->assertNotEmpty(array_filter($r->notes, fn ($n) => str_contains($n, 'several models')));
    }

    public function test_a_failed_model_call_falls_back_to_the_rules(): void
    {
        $this->fakeAi([500, 500, 500]);

        $r = app(SpecReader::class)->read('ryzen 3600 / 1660 super');

        $this->assertTrue($r->modelFailed);
        $this->assertSame('AMD Ryzen 5 3600', $r->cpu['name']);
        $this->assertNull($r->gpu);
    }

    public function test_grounding_checks_model_numbers_and_names(): void
    {
        $this->assertTrue(SpecReader::grounded('NVIDIA GeForce RTX 3060 Laptop GPU', 'legion 5 with a 3060'));
        $this->assertFalse(SpecReader::grounded('Intel Core i5-4460', 'i5 4th gen'));
        $this->assertTrue(SpecReader::grounded('AMD Radeon Vega 8', 'ryzen apu with vega 8 graphics'));
    }
}
