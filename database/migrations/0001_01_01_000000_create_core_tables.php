<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The base tables that the later migrations and the code build on: users and sessions, the
 * Steam catalogue (apps, genres, categories, prices, reviews, media), system requirements, game
 * metrics and collections. Columns follow the models, the Steam scraper
 * (scripts/steam_scraper.py) and the scorer (scripts/score_requirements.py).
 *
 * Columns added by later migrations (metacritic, platforms, support links, content ratings)
 * are deliberately not created here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Signed BIGINT, like production (see the wishlists migration).
            $table->bigInteger('id', true);
            $table->string('username', 100)->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // One row per Steam app; the Steam app id is the key.
        Schema::create('apps', function (Blueprint $table) {
            $table->unsignedInteger('app_id')->primary();
            $table->timestamp('captured_at')->nullable();
            $table->string('type', 32)->default('game');
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->unsignedInteger('steam_appid')->nullable();
            $table->unsignedSmallInteger('required_age')->default(0);
            $table->boolean('is_free')->default(false);
            $table->text('short_description')->nullable();
            $table->longText('detailed_description')->nullable();
            $table->longText('about_the_game')->nullable();
            $table->text('supported_languages')->nullable();
            $table->string('website', 500)->nullable();
            $table->date('release_date')->nullable();
            $table->string('release_date_text', 100)->nullable();
            $table->boolean('coming_soon')->default(false);
            $table->json('developers')->nullable();
            $table->json('publishers')->nullable();
            $table->string('header_image', 500)->nullable();
            $table->string('background_image', 500)->nullable();
            $table->text('content_descriptor_notes')->nullable();
            $table->unsignedInteger('total_recommendations')->default(0);
            $table->unsignedInteger('total_views')->default(0);
            $table->timestamps();
            $table->index('name');
            // The search box uses MATCH ... AGAINST on these.
            $table->fullText(['name', 'short_description']);
            $table->fullText('name');
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('steam_genre_id')->nullable();
            $table->string('name', 100)->unique();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('steam_category_id')->nullable();
            $table->string('name', 100)->unique();
        });

        Schema::create('app_genres', function (Blueprint $table) {
            $table->unsignedInteger('app_id');
            $table->foreignId('genre_id')->constrained('genres')->cascadeOnDelete();
            $table->primary(['app_id', 'genre_id']);
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        Schema::create('app_categories', function (Blueprint $table) {
            $table->unsignedInteger('app_id');
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['app_id', 'category_id']);
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        // Prices are in cents, as Steam returns them.
        Schema::create('pricing', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id')->unique();
            $table->string('currency', 8)->default('USD');
            $table->unsignedInteger('initial_price')->default(0);
            $table->unsignedInteger('final_price')->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->timestamp('updated_at')->nullable();
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id')->unique();
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_positive')->default(0);
            $table->unsignedInteger('total_negative')->default(0);
            $table->string('review_score_desc', 64)->nullable();
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        Schema::create('screenshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id')->index();
            $table->unsignedInteger('steam_screenshot_id')->nullable();
            $table->string('path_thumbnail', 500);
            $table->string('path_full', 500);
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id')->index();
            $table->unsignedInteger('steam_movie_id')->nullable();
            $table->string('name')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('dash_av1', 500)->nullable();
            $table->string('dash_h264', 500)->nullable();
            $table->string('hls_h264', 500)->nullable();
            $table->string('webm_480', 500)->nullable();
            $table->string('mp4_480', 500)->nullable();
            $table->boolean('highlight')->default(false);
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        // System requirements per platform (pc, mac, linux): Steam's HTML, the extracted
        // text per component, and the scores the compatibility check compares against.
        Schema::create('sys_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id');
            $table->string('platform', 16);
            $table->text('min_html')->nullable();
            $table->text('rec_html')->nullable();
            foreach (['min', 'rec'] as $tier) {
                $table->string("{$tier}_cpu", 500)->nullable();
                $table->string("{$tier}_ram", 120)->nullable();
                $table->string("{$tier}_gpu", 500)->nullable();
                $table->string("{$tier}_storage", 120)->nullable();
                $table->string("{$tier}_directx", 60)->nullable();
            }
            foreach (['min', 'rec'] as $tier) {
                foreach (['cpu', 'ram', 'gpu', 'storage', 'overall'] as $part) {
                    $table->decimal("{$tier}_{$part}_score", 8, 2)->nullable();
                }
            }
            $table->unique(['app_id', 'platform']);
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        Schema::create('game_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('app_id')->unique();
            $table->decimal('min_requirement_score', 8, 2)->nullable();
            $table->decimal('rec_requirement_score', 8, 2)->nullable();
            $table->string('intensity_tier', 20)->nullable()->index();
            $table->boolean('has_pc_requirements')->default(false);
            $table->boolean('has_mac_requirements')->default(false);
            $table->boolean('has_linux_requirements')->default(false);
            $table->unsignedInteger('popularity_score')->default(0);
            $table->timestamps();
            $table->foreign('app_id')->references('app_id')->on('apps')->cascadeOnDelete();
        });

        Schema::create('stats_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('stat_type', 40);
            $table->unsignedInteger('app_id')->nullable();
            $table->unsignedBigInteger('hardware_id')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('type', 30)->default('custom');
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('week_year')->nullable();
            $table->unsignedTinyInteger('week_no')->nullable();
            $table->text('intro')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('seo_keywords', 500)->nullable();
            $table->string('hero_image', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('raw_ingest', function (Blueprint $table) {
            $table->unsignedInteger('app_id')->primary();
            $table->timestamp('captured_at')->nullable();
            $table->longText('raw_json');
        });
    }

    public function down(): void
    {
        foreach (['raw_ingest', 'collections', 'stats_tracking', 'game_metrics', 'sys_requirements', 'movies',
            'screenshots', 'reviews', 'pricing', 'app_categories', 'app_genres', 'categories', 'genres', 'apps',
            'sessions', 'password_reset_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
