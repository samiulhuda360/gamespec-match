<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Phase 1: High-value fields
        Schema::table('apps', function (Blueprint $table) {
            $table->integer('metacritic_score')->nullable()->after('total_recommendations');
            $table->string('metacritic_url', 500)->nullable();
            $table->integer('total_achievements')->nullable()->default(0);
            $table->enum('controller_support', ['none', 'partial', 'full'])->default('none');
            $table->integer('dlc_count')->default(0);
            $table->json('dlc_ids')->nullable();
            $table->boolean('has_adult_content')->default(false);
            $table->boolean('has_adult_content_sex')->default(false);
            $table->boolean('has_adult_content_violence')->default(false);
            $table->json('package_ids')->nullable();

            // Indexes
            $table->index('metacritic_score');
            $table->index('total_achievements');
            $table->index('has_adult_content');
        });

        // Phase 2: Content Ratings
        Schema::create('content_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id');
            $table->enum('rating_system', ['esrb', 'pegi', 'usk', 'acb', 'classind', 'other']);
            $table->string('rating', 10);
            $table->text('descriptors')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('app_id')->references('app_id')->on('apps')->onDelete('cascade');
            $table->unique(['app_id', 'rating_system']);
            $table->index('rating_system');
            $table->index('rating');
        });

        // Phase 3: Support & Legal
        Schema::table('apps', function (Blueprint $table) {
            $table->string('support_url', 500)->nullable();
            $table->string('support_email', 255)->nullable();
            $table->text('legal_notice')->nullable();
            $table->string('drm_notice', 500)->nullable();
            $table->string('ext_account_notice', 500)->nullable();
        });

        // Phase 4: Platform flags
        Schema::table('apps', function (Blueprint $table) {
            $table->boolean('supports_windows')->default(false);
            $table->boolean('supports_mac')->default(false);
            $table->boolean('supports_linux')->default(false);

            $table->index(['supports_windows', 'supports_mac', 'supports_linux'], 'idx_platforms');
        });

        // Populate platform flags from existing sys_requirements data
        DB::statement("
            UPDATE apps a
            SET supports_windows = EXISTS(
                SELECT 1 FROM sys_requirements
                WHERE app_id = a.app_id AND platform = 'windows'
            )
        ");

        DB::statement("
            UPDATE apps a
            SET supports_mac = EXISTS(
                SELECT 1 FROM sys_requirements
                WHERE app_id = a.app_id AND platform = 'mac'
            )
        ");

        DB::statement("
            UPDATE apps a
            SET supports_linux = EXISTS(
                SELECT 1 FROM sys_requirements
                WHERE app_id = a.app_id AND platform = 'linux'
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropIndex('idx_platforms');
            $table->dropColumn([
                'supports_windows', 'supports_mac', 'supports_linux',
                'support_url', 'support_email', 'legal_notice', 'drm_notice', 'ext_account_notice',
                'metacritic_score', 'metacritic_url', 'total_achievements',
                'controller_support', 'dlc_count', 'dlc_ids',
                'has_adult_content', 'has_adult_content_sex', 'has_adult_content_violence',
                'package_ids'
            ]);
        });

        Schema::dropIfExists('content_ratings');
    }
};
