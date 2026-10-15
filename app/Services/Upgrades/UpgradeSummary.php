<?php

namespace App\Services\Upgrades;

use App\Ai\AiException;
use App\Ai\LlmClient;
use App\Models\UserHardware;

/**
 * Two or three plain sentences on top of the advisor's numbers. The model only rewords facts
 * it is given: a draft that contains a number not in those facts, or that is too long, is
 * thrown away and the template sentence is used instead.
 */
class UpgradeSummary
{
    private const NAMES = ['gpu' => 'graphics card', 'cpu' => 'processor', 'ram' => 'memory'];

    public function __construct(private readonly LlmClient $llm)
    {
    }

    /** @return array{text: string, source: string} */
    public function write(UserHardware $pc, array $advice): array
    {
        $fallback = $this->template($pc, $advice);
        if (! $this->llm->isLive() || $advice['options'] === []) {
            return ['text' => $fallback, 'source' => 'rules'];
        }

        $facts = $this->facts($pc, $advice);
        try {
            $text = trim($this->llm->text('upgrade-summary',
                'You explain PC upgrade advice to a gamer in two or three short sentences of plain English. '
                .'Use only the facts given: do not add parts, prices, frame rates or numbers that are not in them, '
                .'and write every number as digits. Name the most useful upgrade and what it unlocks. '
                .'No greeting, no lists, no markdown.',
                $facts, 200));
        } catch (AiException) {
            return ['text' => $fallback, 'source' => 'rules'];
        }

        $ok = self::grounded($text, $facts) && mb_strlen($text) >= 40 && mb_strlen($text) <= 600
            && ! preg_match('/[#*\[\]]/', $text);

        return $ok ? ['text' => $text, 'source' => 'ai'] : ['text' => $fallback, 'source' => 'rules'];
    }

    /**
     * Every number in the draft must appear in the facts it was written from. Numbers written
     * as words ("forty", "twenty-seven") are read as numbers first, so they cannot slip past.
     */
    public static function grounded(string $draft, string $facts): bool
    {
        $draft = self::wordsToDigits($draft);
        preg_match_all('/\d+(?:[.,]\d+)?/', $draft, $m);
        preg_match_all('/\d+(?:[.,]\d+)?/', $facts, $f);
        $known = array_map(fn ($n) => str_replace(',', '', $n), $f[0]);
        foreach ($m[0] as $n) {
            if (! in_array(str_replace(',', '', $n), $known, true)) {
                return false;
            }
        }

        return true;
    }

    /** "forty-two" -> "42", "three" -> "3" (up to 99, which covers counts in these summaries). */
    public static function wordsToDigits(string $text): string
    {
        $units = ['zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7,
            'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12, 'thirteen' => 13, 'fourteen' => 14,
            'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17, 'eighteen' => 18, 'nineteen' => 19];
        $tens = ['twenty' => 20, 'thirty' => 30, 'forty' => 40, 'fifty' => 50, 'sixty' => 60, 'seventy' => 70, 'eighty' => 80, 'ninety' => 90];
        $pattern = '/\b('.implode('|', array_keys($tens)).')(?:[- ]('.implode('|', array_slice(array_keys($units), 1, 9)).'))?\b|\b('.implode('|', array_keys($units)).')\b/i';

        return preg_replace_callback($pattern, function ($m) use ($units, $tens) {
            if (($m[1] ?? '') !== '') {
                return (string) ($tens[strtolower($m[1])] + (($m[2] ?? '') !== '' ? $units[strtolower($m[2])] : 0));
            }

            return (string) $units[strtolower($m[3])];
        }, $text);
    }

    private function facts(UserHardware $pc, array $advice): string
    {
        $lines = [
            'PC: '.($pc->cpu_model ?? $pc->cpu).', '.($pc->gpu_model ?? $pc->gpu).', '.(int) $pc->ram_score.' GB RAM',
            "Games checked: {$advice['games']}",
            "Runs at recommended settings now: {$advice['baseline']['recommended']}",
            "Playable (at least minimum settings) now: {$advice['baseline']['playable']}",
        ];
        foreach ($advice['bottlenecks'] as $part => $count) {
            $lines[] = 'Games held below recommended by the '.self::NAMES[$part].": {$count}";
        }
        foreach (array_slice($advice['options'], 0, 3) as $o) {
            $lines[] = 'Upgrading the '.self::NAMES[$o['component']]." to {$o['part']}: {$o['gain_recommended']} more games would run at "
                .'recommended settings'.($o['examples'] ? ' (for example '.implode(', ', array_column($o['examples'], 'name')).')' : '')
                ."; separately, {$o['gain_playable']} games that cannot run today would become playable.";
        }

        return implode("\n", $lines);
    }

    private function template(UserHardware $pc, array $advice): string
    {
        $base = $advice['baseline'];
        $text = "Your PC runs {$base['recommended']} of {$advice['games']} games at recommended settings and can play {$base['playable']}.";
        $worst = collect($advice['bottlenecks'])->sortDesc();
        if ($worst->first() > 0) {
            $text .= ' The '.self::NAMES[$worst->keys()->first()].' holds you back most often ('.$worst->first().' games).';
        }
        if ($best = $advice['options'][0] ?? null) {
            $text .= " The most useful single upgrade is {$best['part']}, which would add {$best['gain_recommended']} games at recommended settings"
                .($best['examples'] ? ', including '.implode(' and ', array_slice(array_column($best['examples'], 'name'), 0, 2)) : '').'.';
        } else {
            $text .= ' None of the common upgrades would add games at recommended settings: your PC already covers this catalogue well.';
        }

        return $text;
    }
}
