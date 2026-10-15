<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add a temporary VARCHAR column
        DB::statement('ALTER TABLE user_hardware ADD COLUMN platform_temp VARCHAR(20) AFTER platform');

        // Step 2: Copy data with transformation
        DB::statement("
            UPDATE user_hardware
            SET platform_temp = CASE
                WHEN platform = 'pc' THEN 'windows_11'
                WHEN platform = 'mac' THEN 'macos'
                WHEN platform = 'linux' THEN 'linux'
                ELSE 'windows_11'
            END
        ");

        // Step 3: Drop the old ENUM column
        DB::statement('ALTER TABLE user_hardware DROP COLUMN platform');

        // Step 4: Add new ENUM column with updated values
        DB::statement("
            ALTER TABLE user_hardware
            ADD COLUMN platform ENUM('windows_10', 'windows_11', 'macos', 'linux')
            NOT NULL DEFAULT 'windows_11'
            AFTER overall_score
        ");

        // Step 5: Copy data from temp to new column
        DB::statement('UPDATE user_hardware SET platform = platform_temp');

        // Step 6: Drop temporary column
        DB::statement('ALTER TABLE user_hardware DROP COLUMN platform_temp');
    }

    public function down(): void
    {
        // Step 1: Add a temporary VARCHAR column
        DB::statement('ALTER TABLE user_hardware ADD COLUMN platform_temp VARCHAR(20) AFTER platform');

        // Step 2: Copy data with reverse transformation
        DB::statement("
            UPDATE user_hardware
            SET platform_temp = CASE
                WHEN platform IN ('windows_10', 'windows_11') THEN 'pc'
                WHEN platform = 'macos' THEN 'mac'
                WHEN platform = 'linux' THEN 'linux'
                ELSE 'pc'
            END
        ");

        // Step 3: Drop the new ENUM column
        DB::statement('ALTER TABLE user_hardware DROP COLUMN platform');

        // Step 4: Add old ENUM column with original values
        DB::statement("
            ALTER TABLE user_hardware
            ADD COLUMN platform ENUM('pc', 'mac', 'linux')
            NOT NULL DEFAULT 'pc'
            AFTER overall_score
        ");

        // Step 5: Copy data from temp to old column
        DB::statement('UPDATE user_hardware SET platform = platform_temp');

        // Step 6: Drop temporary column
        DB::statement('ALTER TABLE user_hardware DROP COLUMN platform_temp');
    }
};
