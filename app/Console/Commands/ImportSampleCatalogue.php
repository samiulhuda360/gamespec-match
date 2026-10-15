<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Services\Scoring\RequirementScorer;
use App\Services\Scoring\RequirementText;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Loads database/data/steam-sample.json (built by scripts/fetch_sample_catalogue.py) into the
 * catalogue tables, and scores every requirement with the same engine the compatibility check
 * uses. The production database is filled by the Python scraper instead; this command makes
 * the app runnable, testable and demo-able from a fresh clone.
 */
class ImportSampleCatalogue extends Command
{
    protected $signature = 'games:import {--file=database/data/steam-sample.json} {--rescore : Only re-score existing requirements}';

    protected $description = 'Import the sample Steam catalogue and score its system requirements';

    public function handle(RequirementScorer $scorer): int
    {
        if ($this->option('rescore')) {
            $this->rescore($scorer);

            return self::SUCCESS;
        }

        $path = base_path($this->option('file'));
        $games = json_decode((string) @file_get_contents($path), true)['games'] ?? null;
        if (! is_array($games)) {
            $this->error("Could not read {$path}.");

            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar(count($games));
        foreach ($games as $game) {
            DB::transaction(fn () => $this->importGame($game, $scorer));
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info(count($games).' games imported and scored.');

        return self::SUCCESS;
    }

    private function importGame(array $g, RequirementScorer $scorer): void
    {
        $id = (int) $g['steam_appid'];
        $platforms = $g['platforms'] ?? [];

        $app = App::firstOrNew(['app_id' => $id]);
        $app->fill([
            'app_id' => $id,
            'captured_at' => now(),
            'type' => $g['type'] ?? 'game',
            'name' => $g['name'],
            'steam_appid' => $id,
            'required_age' => (int) ($g['required_age'] ?? 0),
            'is_free' => (bool) ($g['is_free'] ?? false),
            'short_description' => html_entity_decode((string) ($g['short_description'] ?? ''), ENT_QUOTES | ENT_HTML5),
            'about_the_game' => html_entity_decode((string) ($g['short_description'] ?? ''), ENT_QUOTES | ENT_HTML5),
            'website' => $g['website'] ?? null,
            'release_date' => self::date($g['release_date']['date'] ?? null),
            'coming_soon' => (bool) ($g['release_date']['coming_soon'] ?? false),
            'developers' => $g['developers'] ?? [],
            'publishers' => $g['publishers'] ?? [],
            'header_image' => $g['header_image'] ?? null,
            'content_descriptor_notes' => $g['content_descriptors']['notes'] ?? null,
            'total_recommendations' => (int) ($g['recommendations'] ?? 0),
            'metacritic_score' => $g['metacritic']['score'] ?? null,
            'total_achievements' => (int) ($g['achievements'] ?? 0),
            'controller_support' => in_array($g['controller_support'] ?? null, ['full', 'partial'], true) ? $g['controller_support'] : 'none',
            'supports_windows' => (bool) ($platforms['windows'] ?? false),
            'supports_mac' => (bool) ($platforms['mac'] ?? false),
            'supports_linux' => (bool) ($platforms['linux'] ?? false),
        ]);
        $app->release_date_text = $g['release_date']['date'] ?? null;
        $app->save();

        foreach (['genres' => ['genres', 'steam_genre_id', 'app_genres', 'genre_id'], 'categories' => ['categories', 'steam_category_id', 'app_categories', 'category_id']]
                 as $key => [$table, $steamColumn, $pivot, $foreign]) {
            DB::table($pivot)->where('app_id', $id)->delete();
            foreach ($g[$key] ?? [] as $item) {
                DB::table($table)->updateOrInsert(['name' => $item['description']], [$steamColumn => (int) $item['id']]);
                $rowId = DB::table($table)->where('name', $item['description'])->value('id');
                DB::table($pivot)->insertOrIgnore(['app_id' => $id, $foreign => $rowId]);
            }
        }

        $price = $g['price_overview'] ?? null;
        DB::table('pricing')->updateOrInsert(['app_id' => $id], [
            'currency' => $price['currency'] ?? 'USD',
            'initial_price' => (int) ($price['initial'] ?? 0),
            'final_price' => (int) ($price['final'] ?? 0),
            'discount_percent' => (int) ($price['discount_percent'] ?? 0),
            'updated_at' => now(),
        ]);

        $reviews = $g['reviews'] ?? [];
        DB::table('reviews')->updateOrInsert(['app_id' => $id], [
            'total_reviews' => (int) ($reviews['total_reviews'] ?? 0),
            'total_positive' => (int) ($reviews['total_positive'] ?? 0),
            'total_negative' => (int) ($reviews['total_negative'] ?? 0),
            'review_score_desc' => $reviews['review_score_desc'] ?? null,
        ]);

        DB::table('screenshots')->where('app_id', $id)->delete();
        foreach ($g['screenshots'] ?? [] as $s) {
            DB::table('screenshots')->insert(['app_id' => $id, 'steam_screenshot_id' => $s['id'] ?? null,
                'path_thumbnail' => $s['path_thumbnail'], 'path_full' => $s['path_full']]);
        }

        $req = $g['pc_requirements'] ?? [];
        $this->storeRequirements($id, 'pc', $req['minimum'] ?? null, $req['recommended'] ?? null, $scorer);
        $this->storeMetrics($id, (int) ($reviews['total_reviews'] ?? 0));
    }

    private function storeRequirements(int $appId, string $platform, ?string $minHtml, ?string $recHtml, RequirementScorer $scorer): void
    {
        $row = ['min_html' => $minHtml, 'rec_html' => $recHtml];
        foreach (['min' => $minHtml, 'rec' => $recHtml] as $tier => $html) {
            $kv = RequirementText::parse($html);
            foreach (['cpu', 'ram', 'gpu', 'storage', 'directx'] as $field) {
                $row["{$tier}_{$field}"] = $kv[$field];
            }
            $scores = $html ? $scorer->score($kv) : null;
            foreach (['cpu', 'ram', 'gpu', 'storage', 'overall'] as $part) {
                $row["{$tier}_{$part}_score"] = $scores[$part] ?? null;
            }
        }
        DB::table('sys_requirements')->updateOrInsert(['app_id' => $appId, 'platform' => $platform], $row);
    }

    private function storeMetrics(int $appId, int $reviewCount): void
    {
        $req = DB::table('sys_requirements')->where('app_id', $appId)->where('platform', 'pc')->first();
        $rec = $req?->rec_overall_score ?? $req?->min_overall_score;
        DB::table('game_metrics')->updateOrInsert(['app_id' => $appId], [
            'min_requirement_score' => $req?->min_overall_score,
            'rec_requirement_score' => $req?->rec_overall_score,
            // Tiers on the shared scale (recommended tier, else minimum).
            'intensity_tier' => match (true) {
                $rec === null => null,
                $rec >= 400 => 'ultra',
                $rec >= 250 => 'high-end',
                $rec >= 100 => 'medium',
                default => 'light',
            },
            'has_pc_requirements' => (bool) $req,
            'popularity_score' => $reviewCount,
            'updated_at' => now(), 'created_at' => now(),
        ]);
    }

    private function rescore(RequirementScorer $scorer): void
    {
        $rows = DB::table('sys_requirements')->get(['app_id', 'platform', 'min_html', 'rec_html']);
        foreach ($rows as $r) {
            $this->storeRequirements($r->app_id, $r->platform, $r->min_html, $r->rec_html, $scorer);
            $this->storeMetrics($r->app_id, (int) DB::table('reviews')->where('app_id', $r->app_id)->value('total_reviews'));
        }
        $this->info($rows->count().' requirement rows re-scored.');
    }

    private static function date(?string $text): ?string
    {
        if (! $text) {
            return null;
        }
        foreach (['M j, Y', 'j M, Y', 'Y-m-d', 'F Y', 'M Y'] as $format) {
            try {
                return Carbon::createFromFormat('!'.$format, $text)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}
