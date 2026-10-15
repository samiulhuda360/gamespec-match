<?php

namespace App\Console\Commands;

use App\Ai\AiException;
use App\Ai\Embeddings;
use App\Ai\LlmClient;
use App\Models\App;
use App\Services\Search\GameSearch;
use Illuminate\Console\Command;

/**
 * Stores an embedding for every game so "Ask GameSpec" can rank by meaning. Runs in small,
 * paced batches because free API tiers limit tokens per minute; unchanged games are skipped,
 * so it is safe to run again after an import.
 */
class EmbedGames extends Command
{
    protected $signature = 'games:embed {--batch=16 : games per request} {--pause=3 : seconds between requests}';

    protected $description = 'Embed game descriptions for search ranking (needs AI_API_KEY)';

    public function handle(Embeddings $embeddings, LlmClient $llm): int
    {
        if (! $llm->isLive()) {
            $this->warn('AI is off (no AI_API_KEY): search ranks by shared words, so there is nothing to embed.');

            return self::SUCCESS;
        }

        $games = App::with('genres:id,name')->where('type', 'game')->orderBy('app_id')->get();
        $bar = $this->output->createProgressBar($games->count());
        $embedded = 0;

        foreach ($games->chunk(max(1, (int) $this->option('batch'))) as $chunk) {
            $items = $chunk->mapWithKeys(fn (App $g) => [$g->app_id => ['type' => 'app', 'id' => $g->app_id, 'text' => GameSearch::text($g)]])->all();
            for ($attempt = 1; ; $attempt++) {
                try {
                    $new = $embeddings->warm($items, 'search');
                    break;
                } catch (AiException $e) {
                    if ($attempt >= 5) {
                        $this->newLine();
                        $this->error('Giving up: '.$e->getMessage().' Run the command again later; finished games are kept.');

                        return self::FAILURE;
                    }
                    sleep(15 * $attempt); // rate limited: back off and retry the same batch
                }
            }
            $embedded += $new;
            $bar->advance($chunk->count());
            if ($new > 0) {
                sleep(max(0, (int) $this->option('pause')));
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$embedded} games embedded with {$llm->embeddingModel()}; ".($games->count() - $embedded).' were already up to date.');

        return self::SUCCESS;
    }
}
