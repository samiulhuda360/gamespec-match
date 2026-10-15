<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // CPUs table
        if (!Schema::hasTable('cpus')) {
            Schema::create('cpus', function (Blueprint $table) {
                $table->id();
                $table->enum('manufacturer', ['intel', 'amd', 'apple_silicon', 'qualcomm', 'other']);
                $table->string('name');
                $table->string('generation')->nullable();
                $table->integer('benchmark_score')->nullable(); // PassMark or similar
                $table->integer('cores')->nullable();
                $table->integer('threads')->nullable();
                $table->decimal('base_clock', 4, 2)->nullable(); // GHz
                $table->integer('release_year')->nullable();
                $table->boolean('is_popular')->default(false);
                $table->timestamps();

                $table->index(['manufacturer', 'is_popular']);
                $table->index('benchmark_score');
                $table->index('name');
            });
        }

        // GPUs table
        if (!Schema::hasTable('gpus')) {
            Schema::create('gpus', function (Blueprint $table) {
                $table->id();
                $table->enum('manufacturer', ['nvidia', 'amd', 'intel', 'apple']);
                $table->string('name');
                $table->string('series')->nullable(); // RTX 40, RX 7000, etc.
                $table->integer('vram_gb')->nullable();
                $table->integer('benchmark_score')->nullable(); // 3DMark or similar
                $table->integer('release_year')->nullable();
                $table->boolean('is_popular')->default(false);
                $table->timestamps();

                $table->index(['manufacturer', 'is_popular']);
                $table->index('benchmark_score');
                $table->index('name');
            });
        }

        // Update user_hardware table to use foreign keys
        Schema::table('user_hardware', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('user_hardware', 'cpu_id')) {
                $table->unsignedBigInteger('cpu_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('user_hardware', 'gpu_id')) {
                $table->unsignedBigInteger('gpu_id')->nullable()->after('cpu_id');
            }

            // Add cpu_model and gpu_model columns if they don't exist
            if (!Schema::hasColumn('user_hardware', 'cpu_model')) {
                $table->string('cpu_model')->nullable()->after('cpu_id');
            }

            if (!Schema::hasColumn('user_hardware', 'gpu_model')) {
                $table->string('gpu_model')->nullable()->after('gpu_id');
            }

            // Add ram_gb column if it doesn't exist
            if (!Schema::hasColumn('user_hardware', 'ram_gb')) {
                $table->integer('ram_gb')->nullable()->after('ram');
            }
        });

        // Add foreign keys and indexes separately (to avoid duplicate key errors)
        $this->addForeignKeysAndIndexes();
    }

    /**
     * Add foreign keys and indexes if they don't exist
     */
    private function addForeignKeysAndIndexes(): void
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        // Check for existing foreign keys and indexes using information_schema
        $foreignKeys = DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = 'user_hardware'
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$databaseName]
        );

        $indexes = DB::select(
            "SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = 'user_hardware'",
            [$databaseName]
        );

        $foreignKeyNames = array_column($foreignKeys, 'CONSTRAINT_NAME');
        $indexNames = array_column($indexes, 'INDEX_NAME');

        // Add foreign keys and indexes if they don't exist
        Schema::table('user_hardware', function (Blueprint $table) use ($foreignKeyNames, $indexNames) {
            if (!in_array('user_hardware_cpu_id_foreign', $foreignKeyNames)) {
                $table->foreign('cpu_id')->references('id')->on('cpus')->onDelete('set null');
            }

            if (!in_array('user_hardware_gpu_id_foreign', $foreignKeyNames)) {
                $table->foreign('gpu_id')->references('id')->on('gpus')->onDelete('set null');
            }

            if (!in_array('user_hardware_cpu_id_index', $indexNames)) {
                $table->index('cpu_id');
            }

            if (!in_array('user_hardware_gpu_id_index', $indexNames)) {
                $table->index('gpu_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_hardware', function (Blueprint $table) {
            $table->dropForeign(['cpu_id']);
            $table->dropForeign(['gpu_id']);
            $table->dropColumn(['cpu_id', 'gpu_id']);
        });

        Schema::dropIfExists('gpus');
        Schema::dropIfExists('cpus');
    }
};
