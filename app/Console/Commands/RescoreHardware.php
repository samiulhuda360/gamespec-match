<?php

namespace App\Console\Commands;

use App\Models\UserHardware;
use App\Services\HardwareScorer;
use Illuminate\Console\Command;

class RescoreHardware extends Command
{
    protected $signature = 'hardware:rescore {--id= : Specific hardware ID to rescore}';
    protected $description = 'Re-score hardware profiles using updated scoring logic';

    public function handle(HardwareScorer $scorer): int
    {
        if ($hardwareId = $this->option('id')) {
            $hardware = UserHardware::find($hardwareId);

            if (!$hardware) {
                $this->error("Hardware profile {$hardwareId} not found.");
                return 1;
            }

            $this->info("Re-scoring hardware profile: {$hardware->profile_name} (ID: {$hardware->id})");
            $scorer->scoreHardware($hardware);

            $this->info("✅ Scored successfully!");
            $this->table(
                ['Component', 'Score'],
                [
                    ['CPU', $hardware->cpu_score],
                    ['GPU', $hardware->gpu_score],
                    ['RAM', $hardware->ram_score],
                    ['Overall', $hardware->overall_score],
                ]
            );

            return 0;
        }

        // Re-score all hardware profiles
        $hardwareProfiles = UserHardware::all();
        $count = $hardwareProfiles->count();

        $this->info("Re-scoring {$count} hardware profiles...");
        $this->withProgressBar($hardwareProfiles, function ($hardware) use ($scorer) {
            $scorer->scoreHardware($hardware);
        });

        $this->newLine(2);
        $this->info("✅ Successfully re-scored {$count} hardware profiles!");

        return 0;
    }
}
