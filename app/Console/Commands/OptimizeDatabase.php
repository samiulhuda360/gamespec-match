<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueryOptimizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizeDatabase extends Command
{
    protected $signature = 'db:optimize
                            {--tables : Optimize database tables}
                            {--cache : Clear and warm up cache}
                            {--stats : Show database statistics}
                            {--all : Run all optimizations}';

    protected $description = 'Optimize database performance for large datasets';

    public function handle()
    {
        $runAll = $this->option('all');

        if ($this->option('tables') || $runAll) {
            $this->optimizeTables();
        }

        if ($this->option('cache') || $runAll) {
            $this->optimizeCache();
        }

        if ($this->option('stats') || $runAll) {
            $this->showStats();
        }

        if (!$this->option('tables') && !$this->option('cache') && !$this->option('stats') && !$runAll) {
            $this->info('Please specify an option: --tables, --cache, --stats, or --all');
            $this->info('Example: php artisan db:optimize --all');
        }

        return 0;
    }

    private function optimizeTables()
    {
        $this->info('Optimizing database tables...');

        $tables = [
            'apps',
            'app_genres',
            'app_categories',
            'stats_tracking',
            'pricing',
            'reviews',
            'sys_requirements'
        ];

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to optimize {$table}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('✓ Tables optimized successfully');
    }

    private function optimizeCache()
    {
        $this->info('Clearing cache...');
        Cache::flush();
        $this->info('✓ Cache cleared');

        $this->info('Warming up cache with popular queries...');

        // Warm up homepage cache
        $this->call('route:cache');

        $this->info('✓ Cache warmed up');
    }

    private function showStats()
    {
        $this->info('Database Statistics:');
        $this->newLine();

        $stats = [
            'Total Games' => DB::table('apps')->where('type', 'game')->count(),
            'Total Apps' => DB::table('apps')->count(),
            'Total Genres' => DB::table('genres')->count(),
            'Total Categories' => DB::table('categories')->count(),
            'Stats Records' => DB::table('stats_tracking')->count(),
            'Hardware Profiles' => DB::table('user_hardware')->count(),
            'Compatibility Cache' => DB::table('compatibility_cache')->count(),
        ];

        foreach ($stats as $label => $value) {
            $this->line(sprintf('%-25s %s', $label . ':', number_format($value)));
        }

        $this->newLine();

        // Show table sizes
        $this->info('Table Sizes:');
        $sizes = DB::select("
            SELECT
                table_name AS 'table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ");

        foreach ($sizes as $size) {
            $this->line(sprintf('%-30s %s MB', $size->table . ':', $size->size_mb));
        }

        $this->newLine();

        // Show index usage
        $this->info('Index Statistics:');
        $indexes = DB::select("
            SELECT
                TABLE_NAME as 'table',
                INDEX_NAME as 'index',
                CARDINALITY as cardinality
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND INDEX_NAME != 'PRIMARY'
              AND INDEX_NAME LIKE 'idx_%'
            ORDER BY CARDINALITY DESC
            LIMIT 15
        ");

        foreach ($indexes as $index) {
            $this->line(sprintf('%-25s %-30s %s rows', $index->table, $index->index, number_format($index->cardinality ?? 0)));
        }
    }
}
