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
        Schema::table('user_hardware', function (Blueprint $table) {
            // Add user_id column if it doesn't exist
            if (!Schema::hasColumn('user_hardware', 'user_id')) {
                $table->bigInteger('user_id')->nullable()->after('id'); // users.id is signed
                $table->index('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            // Add is_default flag for primary profile
            if (!Schema::hasColumn('user_hardware', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('profile_name');
            }

            // Update session_id to be nullable (users don't need sessions)
            $table->string('session_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_hardware', function (Blueprint $table) {
            if (Schema::hasColumn('user_hardware', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('user_hardware', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
