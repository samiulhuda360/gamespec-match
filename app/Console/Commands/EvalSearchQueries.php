<?php

namespace App\Console\Commands;

use App\Ai\LlmClient;
use App\Services\Search\QueryInterpreter;
use Illuminate\Console\Command;

/**
 * Field-by-field accuracy of Ask GameSpec's request reading on hand-labelled queries
 * (database/eval/search-queries.json): the rules alone, the model alone, and the model with
 * empty fields filled by the rules (what the app uses).
 */
class EvalSearchQueries extends Command
{
    protected $signature = 'ai:eval-search {--out= : write a Markdown report to this path} {--pause=2 : seconds between model calls (free tiers limit requests per minute)}';

    protected $description = 'Measure how search requests are read (rules vs AI vs AI + rules)';

    private const FIELDS = ['genres', 'features', 'max_price', 'free_only', 'min_review_pct', 'released_after',
        'released_before', 'runs_on', 'similar_to', 'sort', 'no_microtransactions'];

    public function handle(QueryInterpreter $interpreter, LlmClient $llm): int
    {
        $file = json_decode(file_get_contents(database_path('eval/search-queries.json')), true);
        $cases = $file['cases'];

        $systems = ['rules' => fn (string $t) => $interpreter->interpret($t, useAi: false)];
        if ($llm->isLive()) {
            $systems['AI'] = fn (string $t) => $interpreter->interpret($t, useAi: true, backfill: false);
            $systems['AI + rules'] = fn (string $t) => $interpreter->interpret($t, useAi: true, backfill: true);
        } else {
            $this->warn('AI is off (no AI_API_KEY): only the rules are measured.');
        }

        $results = [];
        foreach ($systems as $name => $run) {
            $hits = array_fill_keys(self::FIELDS, 0);
            $perfect = $failed = 0;
            $misses = [];
            foreach ($cases as $i => $case) {
                $expected = $this->expected($file['defaults'], $case['expect']);
                $query = $run($case['text']);
                $failed += $query->modelFailed ? 1 : 0;
                if ($name !== 'rules' && $query->source !== 'rules') {
                    usleep((int) ((float) $this->option('pause') * 1e6));
                }
                $got = $query->toArray();
                $allRight = true;
                foreach (self::FIELDS as $f) {
                    if ($this->same($got[$f], $expected[$f])) {
                        $hits[$f]++;
                    } else {
                        $allRight = false;
                        $misses[] = sprintf('#%d %s: expected %s, got %s', $i + 1, $f, json_encode($expected[$f]), json_encode($got[$f]));
                    }
                }
                $perfect += $allRight ? 1 : 0;
            }
            $results[$name] = compact('hits', 'perfect', 'failed', 'misses');
        }

        $n = count($cases);
        $header = array_merge(['field'], array_keys($results));
        $rows = [];
        foreach (self::FIELDS as $f) {
            $rows[] = array_merge([$f], array_map(fn ($r) => $this->pct($r['hits'][$f], $n), array_values($results)));
        }
        $rows[] = array_merge(['all fields right'], array_map(fn ($r) => $this->pct($r['perfect'], $n), array_values($results)));
        $rows[] = array_merge(['model call failed (rules used)'], array_map(fn ($r) => (string) $r['failed'], array_values($results)));
        $this->table($header, $rows);

        if ($out = $this->option('out')) {
            $md = "# Ask GameSpec eval\n\n"
                ."{$n} hand-labelled search requests (filters, \"like ...\" comparisons, runs-on-my-PC wording, one "
                .'prompt-injection attempt), from `database/eval/search-queries.json`. Run on '.now()->toDateString()
                .($llm->isLive() ? ' with `'.$llm->chatModel().'`' : ' (rules only)').'. Command: `php artisan ai:eval-search`.'."\n\n"
                .'| '.implode(' | ', $header)." |\n|".str_repeat('---|', count($header))."\n";
            foreach ($rows as $row) {
                $md .= '| '.implode(' | ', $row)." |\n";
            }
            $md .= "\nA field is right when it matches the label exactly, including staying empty when the request does not ask for it. "
                ."\"AI + rules\" is what the app uses: the model's answer, with any field it left empty filled by the rules.\n";
            foreach ($results as $name => $r) {
                $md .= "\n## Misses: {$name} (".count($r['misses']).")\n\n".($r['misses'] ? '- '.implode("\n- ", $r['misses']) : 'None.')."\n";
            }
            file_put_contents(base_path($out), $md);
            $this->info('Report written to '.$out);
        }

        return self::SUCCESS;
    }

    private function expected(array $defaults, array $expect): array
    {
        $expected = array_merge($defaults, $expect);
        foreach (['released_after', 'released_before'] as $f) {
            if ($expected[$f] === 'this_year') {
                $expected[$f] = (int) now()->year;
            }
        }
        foreach (['genres', 'features'] as $f) {
            sort($expected[$f]);
        }

        return $expected;
    }

    private function same(mixed $got, mixed $expected): bool
    {
        if (is_float($got) || is_float($expected)) {
            return $got !== null && $expected !== null && abs((float) $got - (float) $expected) < 0.001;
        }

        return $got === $expected;
    }

    private function pct(int $hits, int $n): string
    {
        return round(100 * $hits / $n).'%';
    }
}
