<?php

namespace App\Services\Scoring;

/**
 * The one scale every score in the app uses: a game's requirements and a player's PC
 * are scored the same way, so they can be compared directly.
 *
 * Both sides use the same benchmark divisors and the same RAM size for a full score,
 * so a requirement score and a PC score mean the same thing.
 */
final class ScoreScale
{
    /** CPU benchmark (PassMark-style CPU mark) per score point. */
    public const CPU_DIVISOR = 100;

    /** GPU benchmark (the catalogue's relative score, about 1.5x 3DMark Time Spy graphics) per point. */
    public const GPU_DIVISOR = 35;

    /** RAM counts fully at this size in the overall score. */
    public const RAM_FULL_GB = 32;

    public const WEIGHTS = ['gpu' => 0.50, 'cpu' => 0.35, 'ram' => 0.15];

    public static function cpu(int|float $benchmark): float
    {
        return round(max(0, $benchmark) / self::CPU_DIVISOR, 2);
    }

    public static function gpu(int|float $benchmark): float
    {
        return round(max(0, $benchmark) / self::GPU_DIVISOR, 2);
    }

    /** Weighted score for a whole machine or a whole requirement tier. RAM is in GB. */
    public static function overall(float $cpu, float $ramGb, float $gpu): float
    {
        $ram = min(1000, ($ramGb / self::RAM_FULL_GB) * 1000);

        return round($gpu * self::WEIGHTS['gpu'] + $cpu * self::WEIGHTS['cpu'] + $ram * self::WEIGHTS['ram'], 2);
    }
}
