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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id'); // Match users.id column type (not unsigned)
            $table->unsignedBigInteger('app_id');
            $table->text('notes')->nullable(); // User notes about the game
            $table->decimal('target_price', 8, 2)->nullable(); // Alert when price drops below this
            $table->boolean('notify_on_sale')->default(true); // Email when game goes on sale
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('app_id');
            $table->unique(['user_id', 'app_id']); // Each user can only wishlist a game once

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
