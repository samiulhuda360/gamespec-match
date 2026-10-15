<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('user_hardware')) {
            Schema::create('user_hardware', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index(); // Just an index, no FK
                $table->string('session_id')->nullable()->index();
                $table->string('profile_name')->default('My PC');
                $table->string('cpu')->nullable();
                $table->decimal('cpu_score', 10, 2)->nullable();
                $table->string('ram')->nullable();
                $table->decimal('ram_score', 10, 2)->nullable();
                $table->string('gpu')->nullable();
                $table->decimal('gpu_score', 10, 2)->nullable();
                $table->string('storage')->nullable();
                $table->decimal('storage_score', 10, 2)->nullable();
                $table->decimal('overall_score', 10, 2)->nullable();
                $table->enum('platform', ['pc', 'mac', 'linux'])->default('pc');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::dropIfExists('compatibility_cache');
        
        Schema::create('compatibility_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hardware_id');
            $table->unsignedInteger('app_id');
            $table->enum('can_run_min', ['yes', 'no', 'maybe'])->nullable();
            $table->enum('can_run_rec', ['yes', 'no', 'maybe'])->nullable();
            $table->json('bottlenecks')->nullable();
            $table->timestamps();
            
            $table->unique(['hardware_id', 'app_id']);
            
            $table->foreign('hardware_id')
                  ->references('id')
                  ->on('user_hardware')
                  ->onDelete('cascade');
                  
            $table->foreign('app_id')
                  ->references('app_id')
                  ->on('apps')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('compatibility_cache');
        Schema::dropIfExists('user_hardware');
    }
};