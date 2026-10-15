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
        // Add composite index on app_genres for faster similar games lookup
        if (!$this->indexExists('app_genres', 'idx_genre_app_composite')) {
            Schema::table('app_genres', function (Blueprint $table) {
                $table->index(['genre_id', 'app_id'], 'idx_genre_app_composite');
            });
        }

        // Add composite index on app_categories
        if (!$this->indexExists('app_categories', 'idx_category_app_composite')) {
            Schema::table('app_categories', function (Blueprint $table) {
                $table->index(['category_id', 'app_id'], 'idx_category_app_composite');
            });
        }

        // Add index on apps.total_recommendations for sorting
        if (!$this->indexExists('apps', 'idx_recommendations_app')) {
            Schema::table('apps', function (Blueprint $table) {
                $table->index(['total_recommendations', 'app_id'], 'idx_recommendations_app');
            });
        }

        // Add composite index on apps for latest releases query
        if (!$this->indexExists('apps', 'idx_release_type_app')) {
            Schema::table('apps', function (Blueprint $table) {
                $table->index(['release_date', 'type', 'app_id'], 'idx_release_type_app');
            });
        }

        // Add composite index on apps for free games query
        if (!$this->indexExists('apps', 'idx_free_recommendations_app')) {
            Schema::table('apps', function (Blueprint $table) {
                $table->index(['is_free', 'total_recommendations', 'app_id'], 'idx_free_recommendations_app');
            });
        }

        // Add composite index on stats_tracking for popular games calculation
        if (!$this->indexExists('stats_tracking', 'idx_stat_created_app')) {
            Schema::table('stats_tracking', function (Blueprint $table) {
                $table->index(['stat_type', 'created_at', 'app_id'], 'idx_stat_created_app');
            });
        }

        // Add index on pricing for deals queries
        if (!$this->indexExists('pricing', 'idx_discount_app')) {
            Schema::table('pricing', function (Blueprint $table) {
                $table->index(['discount_percent', 'app_id'], 'idx_discount_app');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('app_genres', 'idx_genre_app_composite')) {
            Schema::table('app_genres', function (Blueprint $table) {
                $table->dropIndex('idx_genre_app_composite');
            });
        }

        if ($this->indexExists('app_categories', 'idx_category_app_composite')) {
            Schema::table('app_categories', function (Blueprint $table) {
                $table->dropIndex('idx_category_app_composite');
            });
        }

        if ($this->indexExists('apps', 'idx_recommendations_app')) {
            Schema::table('apps', function (Blueprint $table) {
                $table->dropIndex('idx_recommendations_app');
            });
        }

        if ($this->indexExists('apps', 'idx_release_type_app')) {
            Schema::table('apps', function (Blueprint $table) {
                $table->dropIndex('idx_release_type_app');
            });
        }

        if ($this->indexExists('apps', 'idx_free_recommendations_app')) {
            Schema::table('apps', function (Blueprint $table) {
                $table->dropIndex('idx_free_recommendations_app');
            });
        }

        if ($this->indexExists('stats_tracking', 'idx_stat_created_app')) {
            Schema::table('stats_tracking', function (Blueprint $table) {
                $table->dropIndex('idx_stat_created_app');
            });
        }

        if ($this->indexExists('pricing', 'idx_discount_app')) {
            Schema::table('pricing', function (Blueprint $table) {
                $table->dropIndex('idx_discount_app');
            });
        }
    }
};
