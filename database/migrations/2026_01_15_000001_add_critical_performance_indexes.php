<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Indexes for the most frequent lookups: requirements by game and platform, prices,
     * reviews, stats and hardware profiles.
     */
    public function up(): void
    {
        // ===== SYS_REQUIREMENTS TABLE =====
        // Used heavily in CompatibilityChecker::findCompatibleGames()
        Schema::table('sys_requirements', function (Blueprint $table) {
            // Single column index on app_id (most frequently filtered)
            $table->index('app_id', 'idx_sysreq_app_id');

            // Composite index for compatibility queries (app_id + platform)
            $table->index(['app_id', 'platform'], 'idx_sysreq_app_platform');

            // Index on platform for filtering
            $table->index('platform', 'idx_sysreq_platform');
        });

        // ===== PRICING TABLE =====
        // Used in game listings and detail pages
        Schema::table('pricing', function (Blueprint $table) {
            // Single column index on app_id
            $table->index('app_id', 'idx_pricing_app_id');

            // Composite index for USD pricing queries
            $table->index(['app_id', 'currency'], 'idx_pricing_app_currency');

            // Index on final_price for price range filtering
            $table->index('final_price', 'idx_pricing_final_price');

            // Index on discount_percent for deals pages (if not exists)
            if (!Schema::hasIndex('pricing', 'idx_discount_app')) {
                $table->index('discount_percent', 'idx_pricing_discount');
            }
        });

        // ===== REVIEWS TABLE =====
        // Used in game listings for review counts
        Schema::table('reviews', function (Blueprint $table) {
            // Single column index on app_id
            $table->index('app_id', 'idx_reviews_app_id');

            // Composite index for review queries (app_id + total_reviews)
            $table->index(['app_id', 'total_reviews'], 'idx_reviews_app_total');

            // Index on total_positive for positive review filtering
            $table->index('total_positive', 'idx_reviews_positive');
        });

        // ===== STATS_TRACKING TABLE =====
        // Used for analytics and popular games
        Schema::table('stats_tracking', function (Blueprint $table) {
            // Composite index for stat type + app queries
            $table->index(['stat_type', 'app_id'], 'idx_stats_type_app');

            // Composite index for time-based queries
            $table->index(['stat_type', 'created_at'], 'idx_stats_type_created');
        });

        // ===== USER_HARDWARE TABLE =====
        // Used for hardware profile lookups
        Schema::table('user_hardware', function (Blueprint $table) {
            // Index on session_id for guest users
            $table->index('session_id', 'idx_hardware_session');

            // Index on user_id for authenticated users (if not exists)
            if (Schema::hasColumn('user_hardware', 'user_id') &&
                !Schema::hasIndex('user_hardware', 'idx_hardware_user')) {
                $table->index('user_id', 'idx_hardware_user');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('user_hardware', function (Blueprint $table) {
            $table->dropIndex('idx_hardware_session');
            if (Schema::hasIndex('user_hardware', 'idx_hardware_user')) {
                $table->dropIndex('idx_hardware_user');
            }
        });

        Schema::table('stats_tracking', function (Blueprint $table) {
            $table->dropIndex('idx_stats_type_app');
            $table->dropIndex('idx_stats_type_created');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_app_id');
            $table->dropIndex('idx_reviews_app_total');
            $table->dropIndex('idx_reviews_positive');
        });

        Schema::table('pricing', function (Blueprint $table) {
            $table->dropIndex('idx_pricing_app_id');
            $table->dropIndex('idx_pricing_app_currency');
            $table->dropIndex('idx_pricing_final_price');
            if (Schema::hasIndex('pricing', 'idx_pricing_discount')) {
                $table->dropIndex('idx_pricing_discount');
            }
        });

        Schema::table('sys_requirements', function (Blueprint $table) {
            $table->dropIndex('idx_sysreq_app_id');
            $table->dropIndex('idx_sysreq_app_platform');
            $table->dropIndex('idx_sysreq_platform');
        });
    }
};
