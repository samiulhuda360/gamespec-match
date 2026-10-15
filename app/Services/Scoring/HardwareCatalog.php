<?php

namespace App\Services\Scoring;

use App\Models\CPU;
use App\Models\GPU;
use Illuminate\Support\Facades\Cache;

/**
 * Finds catalogue parts in free text: a requirement line ("GTX 1060 3GB / RX 580 4GB"), what a
 * player typed ("i5 4460 and a 1060"), or a browser's WebGL renderer string.
 *
 * Each part gets match keys ("nvidia geforce gtx 1060", "gtx 1060", "gtx1060"). A key only
 * matches as a whole word, and the longest matching key wins, so "gtx 1660 ti" is not read as
 * "gtx 1660" and "rx 580" does not match inside "rx 5800".
 */
class HardwareCatalog
{
    /** @var array{cpu: list<array>, gpu: list<array>}|null */
    private ?array $index = null;

    /** @return list<string> the alternatives in a requirement line ("A or B", "A / B") */
    public static function alternatives(string $text): array
    {
        $text = self::normalise($text);
        $parts = preg_split('/\s+or\s+|\s*\/\s*|\s*,\s*|\s*\|\s*|;|\bor equivalent\b|\bequivalent\b/u', $text);

        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    public static function normalise(string $text): string
    {
        $text = mb_strtolower(str_replace(["\u{00ae}", "\u{2122}", '(r)', '(tm)', '®', '™'], '', $text));
        $text = preg_replace('/\b(or|and)\s+(higher|better|newer|above|later|faster)\b|\bminimum\b|\brecommended\b/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Best catalogue part for one piece of text.
     *
     * @return array{id: int, name: string, benchmark: int, vram_gb: ?int, key: string}|null
     */
    public function match(string $kind, string $text): ?array
    {
        $text = ' '.self::normalise($text).' ';
        $best = null;
        foreach ($this->index()[$kind] as $part) {
            foreach ($part['keys'] as $key) {
                if (strlen($key) <= ($best ? strlen($best['key']) : 0)) {
                    continue; // keys are sorted longest first per part; a shorter key cannot win
                }
                if (preg_match('/(?<![a-z0-9])'.preg_quote($key, '/').'(?![a-z0-9])/', $text)) {
                    $best = ['id' => $part['id'], 'name' => $part['name'], 'benchmark' => $part['benchmark'],
                        'vram_gb' => $part['vram_gb'] ?? null, 'key' => $key];
                    break;
                }
            }
        }

        return $best;
    }

    /** Clear the cached index (after the catalogue changes). */
    public function forget(): void
    {
        $this->index = null;
        Cache::forget('hardware-catalog-index');
    }

    private function index(): array
    {
        return $this->index ??= Cache::remember('hardware-catalog-index', 600, fn () => [
            'cpu' => CPU::whereNotNull('benchmark_score')->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'benchmark' => (int) $c->benchmark_score,
                    'keys' => self::cpuKeys($c->name)])->all(),
            'gpu' => GPU::whereNotNull('benchmark_score')->get()
                ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'benchmark' => (int) $g->benchmark_score,
                    'vram_gb' => $g->vram_gb, 'keys' => self::gpuKeys($g->name)])->all(),
        ]);
    }

    /** @return list<string> longest first */
    public static function cpuKeys(string $name): array
    {
        $n = self::normalise($name);
        $keys = [$n, preg_replace('/^(intel|amd)\s+/', '', $n)];
        if (preg_match('/\b(i[3579])[- ]?(\d{4,5}[a-z]{0,2}\d?)\b/', $n, $m)) {       // Core i5-4460, i7-1165G7
            $keys = array_merge($keys, ["{$m[1]}-{$m[2]}", "{$m[1]} {$m[2]}", "core {$m[1]}-{$m[2]}"]);
        }
        if (preg_match('/ryzen (\d) (\d{4}[a-z0-9]{0,3})\b/', $n, $m)) {                 // Ryzen 5 3600
            $keys = array_merge($keys, ["ryzen {$m[1]} {$m[2]}", "ryzen{$m[1]} {$m[2]}", "r{$m[1]} {$m[2]}", "ryzen {$m[2]}"]);
        }
        if (preg_match('/\bfx[- ]?(\d{4})\b/', $n, $m)) {                                  // FX-6300
            $keys = array_merge($keys, ["fx-{$m[1]}", "fx {$m[1]}", "fx{$m[1]}"]);
        }
        if (preg_match('/core 2 (duo|quad) ([a-z]?\d{4})/', $n, $m)) {                     // Core 2 Duo E6600
            $keys = array_merge($keys, ["core 2 {$m[1]} {$m[2]}", "core2 {$m[1]} {$m[2]}", $m[2]]);
        }
        if (preg_match('/^apple (m\d(?: (?:pro|max|ultra))?)$/', $n, $m)) {                 // Apple M1, "MacBook Air M1"
            $keys[] = $m[1];
        }
        if (str_contains($n, 'steam deck')) {
            $keys[] = 'steam deck';
        }

        return self::sorted($keys);
    }

    /** @return list<string> longest first */
    public static function gpuKeys(string $name): array
    {
        $n = self::normalise($name);
        $keys = [$n, preg_replace('/^(nvidia|amd|intel|ati)\s+/', '', $n)];
        // A VRAM variant ("GTX 1060 3GB") only matches when the text names that size.
        $vram = preg_match('/\b(\d{1,2})\s?gb\b/', $n, $v) ? " {$v[1]}gb" : '';
        if (preg_match('/\b(gtx|rtx|gt|gts)\s?(\d{3,4})((?:\s?(?:ti|super))*)((?:\s(?:laptop|mobile)(?:\s?gpu)?)?)/', $n, $m)) {
            $suffix = trim(preg_replace('/\s+/', ' ', $m[3]));
            $base = trim("{$m[1]} {$m[2]} {$suffix}");
            $tight = trim($m[1].$m[2].($suffix !== '' ? " {$suffix}" : ''));
            $glued = $m[1].' '.$m[2].str_replace(' ', '', $suffix);                       // "gtx 1050ti"
            if (trim($m[4]) !== '') {                                                      // laptop parts only match "laptop"/"mobile"
                $keys = array_merge($keys, ["{$base} laptop", "{$base} mobile", "{$tight} laptop", "{$base} laptop gpu", "{$glued} laptop"]);
            } elseif ($vram !== '') {
                $keys = array_merge($keys, [$base.$vram, $base.' '.trim($vram, ' gb').' gb', $tight.$vram]);
            } else {
                $keys = array_merge($keys, [$base, $tight, $glued, "geforce {$base}"]);
            }
        }
        if (preg_match('/\b(rx|r[579]|hd|vega)\s?(\d{2,4})((?:\s?(?:xt|xtx|x|gre))*)\b/', $n, $m)) {
            $suffix = trim(preg_replace('/\s+/', ' ', $m[3]));
            $base = trim("{$m[1]} {$m[2]} {$suffix}");
            $keys = array_merge($keys, [$base, trim($m[1].$m[2].($suffix !== '' ? " {$suffix}" : '')), "radeon {$base}",
                $m[1].' '.$m[2].str_replace(' ', '', $suffix)]);                             // "rx 6600xt"
        }
        if (preg_match('/\b(uhd|hd|iris xe|iris plus|iris)(?: graphics)?\s?(\d{3,4})?\b/', $n, $m) && str_contains($n, 'intel')) {
            $keys[] = trim(($m[1]).' '.($m[2] ?? ''));
            $keys[] = trim(($m[1]).' graphics '.($m[2] ?? ''));
        }
        if (preg_match('/\barc\s?([ab]\d{3})\b/', $n, $m)) {
            $keys = array_merge($keys, ["arc {$m[1]}", $m[1]]);
        }
        if (preg_match('/^apple (m\d(?: (?:pro|max|ultra))?) gpu/', $n, $m)) {              // the GPU of an Apple M-series chip
            $keys = array_merge($keys, ["apple {$m[1]}", $m[1]]);
        }
        if (str_contains($n, 'steam deck')) {
            $keys[] = 'steam deck';
        }

        return self::sorted($keys);
    }

    private static function sorted(array $keys): array
    {
        // Keys under 3 characters would match too much, except Apple's chip names ("m1").
        $keys = array_values(array_unique(array_filter(array_map(fn ($k) => trim(preg_replace('/\s+/', ' ', $k)), $keys),
            fn ($k) => strlen($k) >= 3 || preg_match('/^m\d$/', $k))));
        usort($keys, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $keys;
    }
}
