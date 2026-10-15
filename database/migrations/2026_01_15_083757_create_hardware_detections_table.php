<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hardware_detections', function (Blueprint $table) {
            $table->id();

            // Raw detection strings from browser
            $table->string('gpu_raw', 500)->nullable();
            $table->string('cpu_raw', 255)->nullable();
            $table->integer('cpu_cores')->nullable();
            $table->integer('cpu_threads')->nullable();
            $table->integer('ram_gb')->nullable();
            $table->string('platform', 50)->nullable();
            $table->text('user_agent')->nullable();

            // Matched hardware (if found)
            $table->unsignedBigInteger('matched_gpu_id')->nullable();
            $table->unsignedBigInteger('matched_cpu_id')->nullable();
            $table->boolean('gpu_matched')->default(false);
            $table->boolean('cpu_matched')->default(false);

            // Detection metadata
            $table->string('detection_method', 50)->default('webgl'); // webgl, desktop_app, manual
            $table->ipAddress('ip_address')->nullable();
            $table->integer('detection_count')->default(1); // Increment if same hardware detected again

            $table->timestamps();

            // Indexes for analysis
            $table->index('gpu_raw');
            $table->index('cpu_raw');
            $table->index(['gpu_matched', 'cpu_matched']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_detections');
    }
};
