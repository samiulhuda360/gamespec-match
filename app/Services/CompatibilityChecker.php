<?php

namespace App\Services;

use App\Models\App;
use App\Models\SysRequirement;
use App\Models\UserHardware;
use App\Services\Scoring\ScoreScale;

class CompatibilityChecker
{
    public function checkCompatibility(UserHardware $hardware, App $app): array
    {
        // Uses the loaded relation when the caller eager-loaded it (search results), else queries.
        $platform = self::requirementPlatform($hardware);
        $requirement = $app->relationLoaded('sysRequirements')
            ? $app->sysRequirements->firstWhere('platform', $platform)
            : $app->sysRequirements()->where('platform', $platform)->first();

        return $this->verdictFor($hardware, $requirement);
    }

    /** The sys_requirements.platform value for a profile's operating system. */
    public static function requirementPlatform(UserHardware $hardware): string
    {
        $platform = strtolower(trim((string) $hardware->platform));

        return match (true) {
            in_array($platform, ['windows', 'windows_10', 'windows_11'], true) => 'pc',
            $platform === 'macos' => 'mac',
            default => $platform,
        };
    }

    public function verdictFor(UserHardware $hardware, ?SysRequirement $requirement): array
    {
        if (!$requirement) {
            return [
                'can_run' => 'unknown',
                'verdict' => 'No system requirements available',
                'confidence' => 'low',
                'bottlenecks' => [],
                'details' => null,
            ];
        }

        // Check component-by-component
        $minChecks = [
            'cpu' => $hardware->cpu_score >= ($requirement->min_cpu_score ?? 0),
            'ram' => $hardware->ram_score >= ($requirement->min_ram_score ?? 0),
            'gpu' => $hardware->gpu_score >= ($requirement->min_gpu_score ?? 0),
        ];

        $recChecks = [
            'cpu' => $hardware->cpu_score >= ($requirement->rec_cpu_score ?? 0),
            'ram' => $hardware->ram_score >= ($requirement->rec_ram_score ?? 0),
            'gpu' => $hardware->gpu_score >= ($requirement->rec_gpu_score ?? 0),
        ];

        $bottlenecks = [];
        foreach ($minChecks as $component => $passes) {
            if (!$passes) {
                $bottlenecks[] = strtoupper($component);
            }
        }

        // The GPU matters most for gaming
        $gpuMin = $requirement->min_gpu_score ?? 0;
        
        // If GPU is critically low (less than 70% of minimum), can't run
        $gpuCriticallyLow = $gpuMin > 0 && $hardware->gpu_score < ($gpuMin * 0.7);
        
        // If using integrated graphics and game requires dedicated GPU
        // On the shared scale (ScoreScale): basic integrated graphics (Intel HD/UHD, Vega 8) score
        // under 50; a GTX 750 Ti-class card, the usual floor for "dedicated GPU required", about 65.
        $hasIntegratedGpu = $hardware->gpu_score < 50;
        $requiresDedicatedGpu = $gpuMin >= 60;
        
        // RAM must meet minimum (it's binary - either have enough or don't)
        $hasEnoughRam = $minChecks['ram'];
        
        // Count passed components
        $passedMinComponents = count(array_filter($minChecks));
        $passedRecComponents = count(array_filter($recChecks));

        // The overall-score compensation below counts RAM only up to what the game asks for:
        // memory beyond that does not make up for a slow graphics card or processor.
        $effectiveOverall = fn ($requiredRam) => ScoreScale::overall(
            (float) $hardware->cpu_score,
            $requiredRam > 0 ? min((float) $hardware->ram_score, (float) $requiredRam) : (float) $hardware->ram_score,
            (float) $hardware->gpu_score
        );

        // STRICT MINIMUM CHECK
        if ($gpuCriticallyLow || ($hasIntegratedGpu && $requiresDedicatedGpu)) {
            // GPU is too weak
            $minPass = false;
            $verdict = '❌ Cannot Run - GPU too weak';
            $canRun = 'no';
            $confidence = 'high';
        } elseif (!$hasEnoughRam) {
            // Not enough RAM
            $minPass = false;
            $verdict = '❌ Cannot Run - Insufficient RAM';
            $canRun = 'no';
            $confidence = 'high';
        } elseif ($passedMinComponents >= 3) {
            // All minimum requirements met
            $minPass = true;
        } elseif ($passedMinComponents >= 2 &&
                  $effectiveOverall($requirement->min_ram_score) >= ($requirement->min_overall_score ?? 0) * 1.15) {
            // Pass 2/3 components AND overall score is 115%+ of minimum (compensating)
            $minPass = true;
        } else {
            // Doesn't meet requirements
            $minPass = false;
        }

        // RECOMMENDED CHECK - Balanced logic
        if ($passedRecComponents >= 3) {
            $recPass = true;
        } elseif ($passedRecComponents >= 2) {
            $gpuMargin = $hardware->gpu_score / max(1, $requirement->rec_gpu_score ?? 1);
            $cpuMargin = $hardware->cpu_score / max(1, $requirement->rec_cpu_score ?? 1);
            $ramMargin = $hardware->ram_score / max(1, $requirement->rec_ram_score ?? 1);
            $overallMargin = $effectiveOverall($requirement->rec_ram_score) / max(1, $requirement->rec_overall_score ?? 1);
            
            // Check which components passed
            $gpuPassRec = $recChecks['gpu'];
            $ramPassRec = $recChecks['ram'];
            $cpuPassRec = $recChecks['cpu'];
            
            // If GPU + RAM pass (most critical for gaming)
            if ($gpuPassRec && $ramPassRec) {
                // Allow CPU to be 88%+ of required (compensated by strong GPU/RAM)
                if ($cpuMargin >= 0.88 || $overallMargin >= 0.96) {
                    $recPass = true;
                } else {
                    $recPass = false;
                }
            }
            // If GPU + CPU pass
            elseif ($gpuPassRec && $cpuPassRec) {
                // RAM should be close
                if ($ramMargin >= 0.90 || $overallMargin >= 0.96) {
                    $recPass = true;
                } else {
                    $recPass = false;
                }
            }
            // If CPU + RAM pass (GPU fails - critical!)
            elseif ($cpuPassRec && $ramPassRec) {
                // GPU is most important - need 95%+
                if ($gpuMargin >= 0.95 || $overallMargin >= 0.98) {
                    $recPass = true;
                } else {
                    $recPass = false;
                }
            }
            else {
                $recPass = false;
            }
        } else {
            $recPass = false;
        }

        // Verdict
        if (!isset($verdict)) {
            if (!$minPass) {
                $verdict = '❌ Cannot Run';
                $canRun = 'no';
                $confidence = 'high';
                
                if (!empty($bottlenecks)) {
                    $verdict .= ' - Bottleneck: ' . implode(', ', $bottlenecks);
                }
            } elseif ($recPass) {
                $verdict = '✅ Can Run (Recommended Settings)';
                $canRun = 'yes_recommended';
                $confidence = 'high';
            } else {
                $verdict = '⚠️ Can Run (Minimum Settings)';
                $canRun = 'yes_minimum';
                $confidence = $passedMinComponents >= 2 ? 'medium' : 'low';
                
                if (!empty($bottlenecks)) {
                    $verdict .= ' - May struggle with: ' . implode(', ', $bottlenecks);
                }
            }
        }

        return [
            'can_run' => $canRun,
            'verdict' => $verdict,
            'confidence' => $confidence,
            'bottlenecks' => $bottlenecks,
            'details' => [
                'user_score' => $hardware->overall_score,
                'min_required' => $requirement->min_overall_score,
                'rec_required' => $requirement->rec_overall_score,
                'component_comparison' => [
                    'cpu' => [
                        'user' => $hardware->cpu_score,
                        'min' => $requirement->min_cpu_score,
                        'rec' => $requirement->rec_cpu_score,
                        'passes_min' => $minChecks['cpu'],
                        'passes_rec' => $recChecks['cpu'],
                    ],
                    'ram' => [
                        'user' => $hardware->ram_score,
                        'min' => $requirement->min_ram_score,
                        'rec' => $requirement->rec_ram_score,
                        'passes_min' => $minChecks['ram'],
                        'passes_rec' => $recChecks['ram'],
                    ],
                    'gpu' => [
                        'user' => $hardware->gpu_score,
                        'min' => $requirement->min_gpu_score,
                        'rec' => $requirement->rec_gpu_score,
                        'passes_min' => $minChecks['gpu'],
                        'passes_rec' => $recChecks['gpu'],
                    ],
                ],
            ],
        ];
    }

    public function findCompatibleGames(UserHardware $hardware, int $perPage = 50, bool $paginate = false)
    {
        $platform = self::requirementPlatform($hardware);

        // Build query for games where user meets minimum requirements
        $query = App::select([
                'apps.app_id',
                'apps.name',
                'apps.slug',
                'apps.header_image',
                'apps.is_free',
                'apps.total_recommendations',
                'apps.type',
                'apps.short_description'
            ])
            ->whereHas('sysRequirements', function ($q) use ($hardware, $platform) {
                $q->where('platform', $platform)
                  ->where(function($query) use ($hardware) {
                      // User's overall score meets or exceeds minimum requirements
                      $query->whereRaw('? >= COALESCE(min_overall_score, 0)', [$hardware->overall_score])
                            // OR user passes at least 2 out of 3 core components
                            ->orWhere(function($subQuery) use ($hardware) {
                                $subQuery->whereRaw('? >= COALESCE(min_cpu_score, 0)', [$hardware->cpu_score])
                                         ->whereRaw('? >= COALESCE(min_ram_score, 0)', [$hardware->ram_score]);
                            })
                            ->orWhere(function($subQuery) use ($hardware) {
                                $subQuery->whereRaw('? >= COALESCE(min_cpu_score, 0)', [$hardware->cpu_score])
                                         ->whereRaw('? >= COALESCE(min_gpu_score, 0)', [$hardware->gpu_score]);
                            })
                            ->orWhere(function($subQuery) use ($hardware) {
                                $subQuery->whereRaw('? >= COALESCE(min_ram_score, 0)', [$hardware->ram_score])
                                         ->whereRaw('? >= COALESCE(min_gpu_score, 0)', [$hardware->gpu_score]);
                            });
                  });
            })
            ->with(['sysRequirements' => function($query) use ($platform) {
                $query->where('platform', $platform);
            }])
            ->orderByDesc('total_recommendations');

        // Return paginated or limited results
        // Use simplePaginate to avoid expensive COUNT(*) query on 124K+ games
        return $paginate ? $query->simplePaginate($perPage) : $query->limit($perPage)->get();
    }
}