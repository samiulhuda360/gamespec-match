<?php

namespace App\Services\Upgrades;

use App\Models\App;
use App\Models\CPU;
use App\Models\GPU;
use App\Models\SysRequirement;
use App\Models\UserHardware;
use App\Services\CompatibilityChecker;
use App\Services\Scoring\ScoreScale;
use Illuminate\Support\Collection;

/**
 * "What should I upgrade?" answered by counting, not guessing: the visitor's PC is checked
 * against every game's requirements, then again with each candidate part swapped in. An
 * upgrade's value is the number of extra games it lets the PC run at recommended (and at
 * minimum) settings. The same checker as the game pages gives every verdict.
 */
class UpgradeAdvisor
{
    /** Widely sold upgrade parts, from budget to high end. Only parts faster than the visitor's are offered. */
    public const GPU_PICKS = [
        'NVIDIA GeForce GTX 1650', 'NVIDIA GeForce RTX 3050', 'AMD Radeon RX 6600', 'NVIDIA GeForce RTX 3060',
        'NVIDIA GeForce RTX 4060', 'AMD Radeon RX 7700 XT', 'NVIDIA GeForce RTX 4070 Super', 'NVIDIA GeForce RTX 4080 Super',
    ];

    public const CPU_PICKS = [
        'Intel Core i3-12100', 'AMD Ryzen 5 5600', 'Intel Core i5-12400F', 'AMD Ryzen 5 7600',
        'AMD Ryzen 7 5800X3D', 'AMD Ryzen 7 7800X3D',
    ];

    public const RAM_PICKS = [16, 32];

    public function __construct(private readonly CompatibilityChecker $checker)
    {
    }

    /**
     * @return array{baseline: array, bottlenecks: array<string, int>, options: list<array>, target: ?array, games: int}
     */
    public function advise(UserHardware $pc, ?App $target = null): array
    {
        $requirements = $this->requirements($pc);
        $baseline = $this->tally($pc, $requirements);

        $options = [];
        foreach ($this->candidates($pc) as $candidate) {
            $upgraded = $this->withPart($pc, $candidate);
            $after = $this->tally($upgraded, $requirements);
            $gainRec = $after['recommended'] - $baseline['recommended'];
            $gainMin = $after['playable'] - $baseline['playable'];
            if ($gainRec <= 0 && $gainMin <= 0) {
                continue;
            }
            $options[] = $candidate + [
                'gain_recommended' => $gainRec,
                'gain_playable' => $gainMin,
                'examples' => array_slice(array_values(array_diff_key($after['recommended_games'], $baseline['recommended_games'])), 0, 4),
            ];
        }
        // Most games unlocked at recommended first; a cheaper step wins a tie.
        usort($options, fn ($a, $b) => [$b['gain_recommended'], $b['gain_playable'], $a['benchmark']] <=> [$a['gain_recommended'], $a['gain_playable'], $b['benchmark']]);

        return [
            'games' => $requirements->count(),
            'baseline' => ['recommended' => $baseline['recommended'], 'playable' => $baseline['playable']],
            'bottlenecks' => $baseline['bottlenecks'],
            'options' => $this->bestPerComponent($options),
            'target' => $target ? $this->forGame($pc, $target) : null,
        ];
    }

    /** What one game needs, part by part, with the slowest listed upgrade that would be enough. */
    public function forGame(UserHardware $pc, App $game): ?array
    {
        $req = $game->sysRequirements()->where('platform', CompatibilityChecker::requirementPlatform($pc))->first();
        if (! $req) {
            return null;
        }
        $verdict = $this->checker->verdictFor($pc, $req);
        $needs = [];
        foreach (['gpu' => 'rec_gpu_score', 'cpu' => 'rec_cpu_score', 'ram' => 'rec_ram_score'] as $part => $column) {
            $required = (float) ($req->{$column} ?? 0);
            $mine = (float) $pc->{$part.'_score'};
            if ($required <= 0 || $mine >= $required) {
                continue;
            }
            $needs[$part] = [
                'required' => $required,
                'yours' => $mine,
                'suggestion' => match ($part) {
                    'gpu' => $this->slowestPick(GPU::class, self::GPU_PICKS, fn ($b) => ScoreScale::gpu($b), $required),
                    'cpu' => $this->slowestPick(CPU::class, self::CPU_PICKS, fn ($b) => ScoreScale::cpu($b), $required),
                    'ram' => ($gb = collect(self::RAM_PICKS)->first(fn ($gb) => $gb >= $required)) ? $gb.' GB' : null,
                },
            ];
        }

        return [
            'name' => $game->name,
            'slug' => $game->slug,
            'verdict' => $verdict['can_run'],
            'needs' => $needs,
            'requirements' => ['cpu' => $req->rec_cpu ?: $req->min_cpu, 'gpu' => $req->rec_gpu ?: $req->min_gpu, 'ram' => $req->rec_ram ?: $req->min_ram],
        ];
    }

    /** @return Collection<int, SysRequirement> with the game's name on each row */
    private function requirements(UserHardware $pc): Collection
    {
        return SysRequirement::query()
            ->join('apps', 'apps.app_id', '=', 'sys_requirements.app_id')
            ->where('sys_requirements.platform', CompatibilityChecker::requirementPlatform($pc))
            ->whereNotNull('sys_requirements.min_overall_score')
            ->where('apps.type', 'game')
            ->orderByDesc('apps.total_recommendations')
            ->limit(2000) // the most-played games are what people upgrade for
            ->get(['sys_requirements.*', 'apps.name as game_name', 'apps.slug as game_slug']);
    }

    private function tally(UserHardware $pc, Collection $requirements): array
    {
        $recommended = [];
        $playable = 0;
        $bottlenecks = ['gpu' => 0, 'cpu' => 0, 'ram' => 0];
        foreach ($requirements as $req) {
            $v = $this->checker->verdictFor($pc, $req);
            if ($v['can_run'] === 'yes_recommended') {
                $recommended[$req->app_id] = ['name' => $req->game_name, 'slug' => $req->game_slug];
            }
            if (in_array($v['can_run'], ['yes_recommended', 'yes_minimum'], true)) {
                $playable++;
            }
            if ($v['can_run'] !== 'yes_recommended') {
                foreach ($bottlenecks as $part => $_) {
                    if (! ($v['details']['component_comparison'][$part]['passes_rec'] ?? true)) {
                        $bottlenecks[$part]++;
                    }
                }
            }
        }

        return ['recommended' => count($recommended), 'recommended_games' => $recommended, 'playable' => $playable, 'bottlenecks' => $bottlenecks];
    }

    /** @return list<array{component: string, part: string, benchmark: float, score: float}> */
    private function candidates(UserHardware $pc): array
    {
        $out = [];
        foreach (GPU::whereIn('name', self::GPU_PICKS)->get() as $gpu) {
            $score = ScoreScale::gpu($gpu->benchmark_score);
            if ($score > (float) $pc->gpu_score) {
                $out[] = ['component' => 'gpu', 'part' => $gpu->name, 'benchmark' => (float) $gpu->benchmark_score, 'score' => $score];
            }
        }
        foreach (CPU::whereIn('name', self::CPU_PICKS)->get() as $cpu) {
            $score = ScoreScale::cpu($cpu->benchmark_score);
            if ($score > (float) $pc->cpu_score) {
                $out[] = ['component' => 'cpu', 'part' => $cpu->name, 'benchmark' => (float) $cpu->benchmark_score, 'score' => $score];
            }
        }
        foreach (self::RAM_PICKS as $gb) {
            if ($gb > (float) $pc->ram_score) {
                $out[] = ['component' => 'ram', 'part' => $gb.' GB RAM', 'benchmark' => (float) $gb, 'score' => (float) $gb];
            }
        }

        return $out;
    }

    /** The PC with one part swapped, scored like a saved profile (never written to the database). */
    private function withPart(UserHardware $pc, array $candidate): UserHardware
    {
        $copy = $pc->replicate();
        $copy->{$candidate['component'].'_score'} = $candidate['score'];
        $copy->overall_score = ScoreScale::overall((float) $copy->cpu_score, (float) $copy->ram_score, (float) $copy->gpu_score);

        return $copy;
    }

    /** Up to two options per component: the biggest gain, and the cheapest step that gets most of it. */
    private function bestPerComponent(array $options): array
    {
        $out = [];
        foreach (collect($options)->groupBy('component') as $group) {
            $best = $group->first();
            $out[] = $best;
            $step = $group->filter(fn ($o) => $o['benchmark'] < $best['benchmark'] && $o['gain_recommended'] >= 0.6 * $best['gain_recommended'])
                ->sortBy('benchmark')->first();
            if ($step) {
                $out[] = $step;
            }
        }
        usort($out, fn ($a, $b) => [$b['gain_recommended'], $b['gain_playable']] <=> [$a['gain_recommended'], $a['gain_playable']]);

        return $out;
    }

    private function slowestPick(string $model, array $names, callable $toScore, float $required): ?string
    {
        return $model::whereIn('name', $names)->orderBy('benchmark_score')->get()
            ->first(fn ($part) => $toScore($part->benchmark_score) >= $required)?->name;
    }
}
