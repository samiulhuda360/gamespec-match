<?php

namespace App\Services\Specs;

use App\Services\Scoring\HardwareCatalog;

/**
 * Reads a PC description without a model: catalogue part names found in the text, the RAM
 * size, the operating system and whether it is a laptop. Free, instant and predictable, so it
 * always runs first; the model is only asked about what this could not place.
 */
class RuleSpecParser
{
    private const RAM_SIZES = [2, 3, 4, 6, 8, 12, 16, 24, 32, 48, 64, 96, 128, 192, 256];

    public function __construct(private readonly HardwareCatalog $catalog)
    {
    }

    public function parse(string $text): SpecReading
    {
        $t = HardwareCatalog::normalise($text);
        $reading = new SpecReading(
            ramGb: self::ram($t),
            platform: self::platform($t),
            laptop: self::looksLikeLaptop($t),
        );

        // "i5-12400F" when the catalogue only has the i5-12400: try again without the suffix.
        $reading->cpu = $this->catalog->match('cpu', $t) ?? $this->catalog->match('cpu', self::withoutCpuSuffix($t));
        $reading->gpu = $this->catalog->match('gpu', $t);

        if ($reading->gpu && $reading->laptop && ! preg_match('/laptop|mobile|max-q/i', $reading->gpu['name'])) {
            // A laptop's "RTX 3060" is the slower laptop part; use it when the catalogue has it.
            $short = preg_replace('/^(?:(?:nvidia|amd|geforce|radeon)\s+)+/', '', $reading->gpu['key']);
            $reading->gpu = $this->catalog->match('gpu', $short.' laptop') ?? $reading->gpu;
        }

        foreach (['cpu', 'gpu'] as $kind) {
            if ($reading->{$kind}) {
                $reading->how[$kind] = 'text';
            }
        }

        return $reading;
    }

    /** Installed memory in GB, or null. Video memory and storage sizes are skipped. */
    public static function ram(string $t): ?int
    {
        $patterns = [
            // "16gb ram", "16 gigs of ram", "16gb ddr4", "16 gb of memory"
            '/(\d{1,3}(?:[.,]\d+)?)\s*(?:gb|gigs?|g)\b\s*(?:of\s+)?(?:ddr\d\w*\s*)?(?:ram|memory|ddr\d)/',
            // "ram: 16 gb", "memory 15.9 gb", "installed physical memory (ram) 16.0 gb"
            '/\b(?:ram|memory)\b[^\d]{0,12}?(\d{1,3}(?:[.,]\d+)?)\s*(?:gb|g)\b/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $t, $m)) {
                return self::plausible((float) str_replace(',', '.', $m[1]));
            }
        }
        // "memory: 16384mb ram" (dxdiag)
        if (preg_match('/\b(?:ram|memory)\s*[:=]?\s*(\d{4,6})\s*mb\b/', $t, $m)
            || preg_match('/(\d{4,6})\s*mb\s*(?:of\s+)?(?:ram|memory)/', $t, $m)) {
            return self::plausible((int) $m[1] / 1024);
        }

        // A bare "16gb" or "8 gigs" that is not the graphics card's memory or a drive: "i5 4460, 1060 6gb, 16gb".
        preg_match_all('/(?<![\w.])(\d{1,3})\s*(?:gb|gigs?)\b(?!\s*(?:ssd|hdd|nvme|m\.2|drive|storage|vram|gddr|graphics|video))/', $t, $all, PREG_OFFSET_CAPTURE);
        foreach ($all[1] as [$number, $offset]) {
            $before = substr($t, max(0, $offset - 20), min(20, $offset));
            if (preg_match('/(?:gtx|rtx|\brx|\bgt\b|radeon|geforce|\barc\b|vega|quadro)[^,;|\/+&]*$/', $before)) {
                continue; // "gtx 1060 6gb" is video memory
            }
            if (in_array((int) $number, self::RAM_SIZES, true) && (int) $number >= 4) {
                return (int) $number;
            }
        }

        return null;
    }

    public static function platform(string $t): ?string
    {
        return match (true) {
            (bool) preg_match('/\bwin(?:dows)?\s*11\b|\bw11\b/', $t) => 'windows_11',
            (bool) preg_match('/\bwin(?:dows)?\s*10\b|\bw10\b/', $t) => 'windows_10',
            (bool) preg_match('/\bmac\s?os\b|\bmacbook|\bimac\b|\bmac (?:mini|studio|pro)\b|\bos x\b|\bosx\b/', $t) => 'macos',
            (bool) preg_match('/\blinux\b|\bubuntu\b|\bfedora\b|\bmint\b|\bpop!?_?os\b|\bsteam ?deck\b|\bsteamos\b|\barch linux\b/', $t) => 'linux',
            default => null,
        };
    }

    public static function looksLikeLaptop(string $t): bool
    {
        return (bool) preg_match('/\b(laptop|notebook|ultrabook|legion|zephyrus|rog strix g\d*|tuf (?:gaming|dash)|nitro [57v]|predator helios|omen 1[5-7]|victus|alienware [mx]\d+|razer blade|macbook|thinkpad|ideapad|vivobook|zenbook|inspiron|aspire|pavilion|max-q)\b/', $t)
            || preg_match('/\bi[3579]-?\d{4,5}(?:h|hk|hs|hx|u|g\d)\b/', $t)
            || preg_match('/\bryzen [3579] \d{4}(?:h|hs|hx|u)\b/', $t);
    }

    private static function withoutCpuSuffix(string $t): string
    {
        return preg_replace('/\b(i[3579][- ]?\d{4,5}|ryzen [3579] \d{4})(?:kf|ks|k|f|t|s)\b/', '$1', $t);
    }

    private static function plausible(float $gb): ?int
    {
        $gb = (int) round($gb);

        return $gb >= 1 && $gb <= 512 ? $gb : null;
    }
}
