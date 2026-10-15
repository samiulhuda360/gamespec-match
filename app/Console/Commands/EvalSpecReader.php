<?php

namespace App\Console\Commands;

use App\Ai\LlmClient;
use App\Services\Specs\SpecReader;
use Illuminate\Console\Command;

/**
 * Accuracy of "Describe your PC" on hand-labelled descriptions (database/eval/pc-descriptions.json):
 * the rules alone, and the rules with the model filling the gaps (what the app uses).
 *
 * "Wrong part" counts answers that would select the wrong CPU or GPU in the form. That is the
 * costly mistake (the visitor may not notice it), so it is reported apart from plain misses,
 * where the field is simply left for the visitor.
 */
class EvalSpecReader extends Command
{
    protected $signature = 'ai:eval-specs {--out= : write a Markdown report to this path}';

    protected $description = 'Measure "Describe your PC" against labelled descriptions (rules vs rules + AI)';

    public function handle(SpecReader $reader, LlmClient $llm): int
    {
        $cases = json_decode(file_get_contents(database_path('eval/pc-descriptions.json')), true)['cases'];

        $systems = ['rules' => false];
        if ($llm->isLive()) {
            $systems['rules + AI'] = true;
        } else {
            $this->warn('AI is off (no AI_API_KEY): only the rules are measured.');
        }

        $results = [];
        foreach ($systems as $name => $useAi) {
            $hits = ['cpu' => 0, 'gpu' => 0, 'ram_gb' => 0];
            $perfect = $wrong = $asked = $failed = 0;
            $misses = [];
            foreach ($cases as $i => $case) {
                $reading = $reader->read($case['text'], $useAi);
                $asked += $reading->source === 'rules+ai' || $reading->modelFailed ? 1 : 0;
                $failed += $reading->modelFailed ? 1 : 0;

                $got = ['cpu' => $reading->cpu['name'] ?? null, 'gpu' => $reading->gpu['name'] ?? null, 'ram_gb' => $reading->ramGb];
                $allRight = true;
                foreach ($hits as $field => $_) {
                    if ($got[$field] === $case[$field]) {
                        $hits[$field]++;

                        continue;
                    }
                    $allRight = false;
                    if ($field !== 'ram_gb' && $got[$field] !== null) {
                        $wrong++;
                    }
                    $misses[] = sprintf('#%d %s: expected %s, got %s', $i + 1, $field,
                        json_encode($case[$field]), json_encode($got[$field]));
                }
                $perfect += $allRight ? 1 : 0;
            }
            $results[$name] = compact('hits', 'perfect', 'wrong', 'asked', 'failed', 'misses');
        }

        $n = count($cases);
        $header = array_merge(['', ...array_keys($results)]);
        $rows = [
            ...array_map(fn ($f, $label) => array_merge([$label], array_map(fn ($r) => $this->pct($r['hits'][$f], $n), array_values($results))),
                ['cpu', 'gpu', 'ram_gb'], ['CPU right', 'GPU right', 'RAM right']),
            array_merge(['all three right'], array_map(fn ($r) => $this->pct($r['perfect'], $n), array_values($results))),
            array_merge(['wrong part selected'], array_map(fn ($r) => (string) $r['wrong'], array_values($results))),
            array_merge(['model asked'], array_map(fn ($r) => $r['asked'].' of '.$n, array_values($results))),
            array_merge(['model call failed (rules used)'], array_map(fn ($r) => (string) $r['failed'], array_values($results))),
        ];
        $this->table($header, $rows);

        if ($out = $this->option('out')) {
            $md = "# \"Describe your PC\" eval\n\n"
                ."{$n} hand-labelled descriptions (casual text, misspellings, laptops, dxdiag and System Information pastes, "
                .'vague ones where the right answer is to leave the field empty, and one prompt-injection attempt), '
                .'from `database/eval/pc-descriptions.json`. Run on '.now()->toDateString()
                .($llm->isLive() ? ' with `'.$llm->chatModel().'`' : ' (rules only)').'. Command: `php artisan ai:eval-specs`.'."\n\n"
                .'| '.implode(' | ', $header)." |\n|".str_repeat('---|', count($header))."\n";
            foreach ($rows as $row) {
                $md .= '| '.implode(' | ', $row)." |\n";
            }
            $md .= "\nThe rules always run first. The model is only asked when a part is still missing, and its answer is used only if "
                ."the catalogue has that part and the part's model number appears in the visitor's text. "
                ."\"Wrong part selected\" counts answers that would pick the wrong CPU or GPU in the form; a field left empty is a plain miss.\n";
            foreach ($results as $name => $r) {
                $md .= "\n## Misses: {$name} (".count($r['misses']).")\n\n".($r['misses'] ? '- '.implode("\n- ", $r['misses']) : 'None.')."\n";
            }
            file_put_contents(base_path($out), $md);
            $this->info('Report written to '.$out);
        }

        return self::SUCCESS;
    }

    private function pct(int $hits, int $n): string
    {
        return round(100 * $hits / $n).'%';
    }
}
