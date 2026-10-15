<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per model call: what it was for, how long it took, and whether it worked.
        Schema::create('ai_calls', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 40)->index();
            $table->string('model', 80);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->boolean('ok')->default(true);
            $table->boolean('cached')->default(false);
            $table->string('error', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        // Embedding vectors (float32, packed) for game descriptions, keyed by a hash of the
        // text they were made from, so a game is only re-embedded when its text changes.
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 40);
            $table->unsignedBigInteger('owner_id');
            $table->string('model', 80);
            $table->string('text_hash', 64);
            $table->binary('vector');
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
        Schema::dropIfExists('ai_calls');
    }
};
