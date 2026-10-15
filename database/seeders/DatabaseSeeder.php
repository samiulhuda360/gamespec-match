<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * A complete demo from a fresh database: the hardware catalogue, the sample Steam
 * catalogue (database/data/steam-sample.json, scored on import), a demo account
 * and the collection pages.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([HardwareSeeder::class, ExpandedHardwareSeeder::class, RequirementHardwareSeeder::class]);

        Artisan::call('games:import');
        $this->command?->info(trim(Artisan::output()));

        User::firstOrCreate(['email' => 'demo@example.com'], [
            'username' => 'demo',
            'password_hash' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        foreach ([
            ['slug' => 'trending-this-week', 'title' => 'Trending this week', 'type' => 'week'],
            ['slug' => 'best-of-'.now()->year, 'title' => 'Best of '.now()->year, 'type' => 'year', 'year' => now()->year],
            ['slug' => 'latest-releases', 'title' => 'Latest releases', 'type' => 'latest'],
        ] as $c) {
            Collection::updateOrCreate(['slug' => $c['slug']], $c + ['is_published' => true, 'published_at' => now()]);
        }
    }
}
