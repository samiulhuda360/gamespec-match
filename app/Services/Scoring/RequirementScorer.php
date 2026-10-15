<?php

namespace App\Services\Scoring;

/**
 * Scores one tier (minimum or recommended) of a game's requirements on the ScoreScale.
 *
 * Requirements usually list alternatives ("Intel Core i5-4460 or AMD FX-6300"). A PC with either
 * part meets the requirement, so the tier's score is the WEAKER alternative; the stronger one
 * would make games look harder to run than they are.
 *
 * When no catalogue part matches, the score falls back to the CPU family or clock speed, or to
 * the GPU's VRAM or DirectX level. The method is recorded so the
 * explanation (and the eval) can tell a matched part from a guess.
 */
class RequirementScorer
{
    public function __construct(private readonly HardwareCatalog $catalog)
    {
    }

    /**
     * @param  array{cpu: ?string, ram: ?string, gpu: ?string, storage: ?string, directx: ?string}  $kv
     * @return array{cpu: float, ram: float, gpu: float, storage: float, overall: float, matched: array}
     */
    public function score(array $kv): array
    {
        $cpu = $this->component('cpu', (string) ($kv['cpu'] ?? ''));
        $gpu = $this->component('gpu', trim(($kv['gpu'] ?? '').' '.($kv['directx'] ?? '')));
        $ram = self::gb((string) ($kv['ram'] ?? ''), 2.0);
        $storage = self::gb((string) ($kv['storage'] ?? ''), 10.0);

        return [
            'cpu' => $cpu['score'], 'ram' => $ram, 'gpu' => $gpu['score'], 'storage' => $storage,
            'overall' => ScoreScale::overall($cpu['score'], $ram, $gpu['score']),
            'matched' => ['cpu' => $cpu, 'gpu' => $gpu],
        ];
    }

    /** @return array{score: float, method: string, parts: list<string>} */
    public function component(string $kind, string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['score' => 0.0, 'method' => 'none', 'parts' => []];
        }

        $found = [];
        foreach (HardwareCatalog::alternatives($text) as $alternative) {
            if ($hit = $this->catalog->match($kind, $alternative)) {
                $found[$hit['name']] = $kind === 'cpu' ? ScoreScale::cpu($hit['benchmark']) : ScoreScale::gpu($hit['benchmark']);
            }
        }
        if ($found) {
            // The weakest listed part is enough to meet the requirement.
            return ['score' => min($found), 'method' => 'catalog', 'parts' => array_keys($found)];
        }

        $t = HardwareCatalog::normalise($text);
        if ($kind === 'gpu') {
            if (preg_match('/integrated|onboard|no dedicated|intel hd|intel uhd|any gpu|any graphics/', $t)) {
                return ['score' => 20.0, 'method' => 'integrated', 'parts' => []];
            }
            if (preg_match('/(\d+(?:\.\d+)?)\s*gb\s*(?:of\s*)?(?:vram|video|graphics|dedicated)?/', $t, $m) && (float) $m[1] <= 24) {
                // About 40 points per GB: a 4 GB card is GTX 1650 / RX 570 class (~170).
                return ['score' => max(20.0, min(400.0, (float) $m[1] * 40)), 'method' => 'vram', 'parts' => []];
            }
            if (preg_match('/(\d+)\s*mb/', $t, $m)) {
                return ['score' => max(15.0, min(60.0, (int) $m[1] / 20)), 'method' => 'vram', 'parts' => []];
            }
            foreach (['12' => 120.0, '11' => 80.0, '10' => 50.0, '9' => 30.0] as $dx => $score) {
                if (preg_match('/(direct\s*x|dx)\s*'.$dx.'\b|version '.$dx.'\b/', $t)) {
                    return ['score' => $score, 'method' => 'directx', 'parts' => []];
                }
            }

            return ['score' => 60.0, 'method' => 'default', 'parts' => []];
        }

        // A family without a model ("Intel Core i5", "quad-core"): a cautious, older member of it.
        foreach ([
            '/ryzen 9/' => 200.0, '/ryzen 7/' => 130.0, '/ryzen 5/' => 90.0, '/ryzen 3/' => 60.0,
            '/\bi9\b/' => 90.0, '/\bi7\b/' => 60.0, '/\bi5\b/' => 45.0, '/\bi3\b/' => 25.0,
            '/\bfx\b/' => 40.0, '/core 2 quad|quad[- ]?core|4 cores/' => 25.0, '/athlon|phenom/' => 18.0,
            '/pentium/' => 15.0, '/core 2 duo|dual[- ]?core|2 cores/' => 12.0, '/celeron/' => 10.0,
        ] as $pattern => $score) {
            if (preg_match($pattern, $t)) {
                return ['score' => $score, 'method' => 'family', 'parts' => []];
            }
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*ghz/', $t, $m)) {
            // Clock speed alone says little; about 20 points per GHz (a 3 GHz CPU of unknown age ~ 60).
            return ['score' => max(10.0, min(100.0, (float) $m[1] * 20)), 'method' => 'clock', 'parts' => []];
        }

        return ['score' => 40.0, 'method' => 'default', 'parts' => []];
    }

    /** "8 GB RAM" -> 8, "512 MB" -> 0.5, "1 TB" -> 1024. */
    public static function gb(string $text, float $default): float
    {
        $t = mb_strtolower($text);
        if ($t === '') {
            return 0.0;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*tb/', $t, $m)) {
            return (float) str_replace(',', '.', $m[1]) * 1024;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*gb/', $t, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }
        if (preg_match('/(\d+)\s*mb/', $t, $m)) {
            return round((int) $m[1] / 1024, 2);
        }

        return $default;
    }
}
