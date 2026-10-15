<?php

namespace App\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Text vectors for similarity: the provider's embedding model when AI is on, or a
 * lexical fallback (hashed word and word-pair counts) when it is off or fails.
 *
 * Vectors from different models are never compared: a ranking is computed with one
 * model end to end. Document vectors are stored per (owner, model) with a hash of the
 * text, so an unchanged game is never embedded twice; query vectors are cached.
 */
class Embeddings
{
    public const LEXICAL = 'lexical-v1';

    private const DIMENSIONS = 512;

    public function __construct(private readonly LlmClient $llm)
    {
    }

    /**
     * Cosine similarity of a query to each item.
     *
     * @param  array<string|int, array{type: string, id: int, text: string}>  $items  keyed by any caller key
     * @return array{model: string, scores: array<string|int, float>}
     */
    public function rank(string $query, array $items, string $feature = 'embeddings', int $maxNew = PHP_INT_MAX): array
    {
        if ($items === []) {
            return ['model' => self::LEXICAL, 'scores' => []];
        }

        if ($this->llm->isLive()) {
            try {
                $vectors = $this->stored($items, $feature, maxNew: $maxNew);
                $q = $this->queryVector($query, $feature);

                return ['model' => $this->llm->embeddingModel(),
                    'scores' => array_map(fn ($v) => self::cosine($q, $v), $vectors)];
            } catch (AiException) {
                // fall through: rank the whole batch with lexical vectors instead
            }
        }

        $q = self::lexical($query);

        return ['model' => self::LEXICAL,
            'scores' => array_map(fn ($item) => self::cosine($q, self::lexical($item['text'])), $items)];
    }

    /**
     * Make sure every item has a stored provider vector (used by games:embed).
     *
     * @return int how many items had to be embedded
     */
    public function warm(array $items, string $feature = 'embeddings'): int
    {
        return count($this->stored($items, $feature, missingOnly: true));
    }

    public static function cosine(array $a, array $b): float
    {
        $dot = $na = $nb = 0.0;
        foreach ($a as $i => $x) {
            $y = $b[$i] ?? 0.0;
            $dot += $x * $y;
            $na += $x * $x;
            $nb += $y * $y;
        }

        return ($na > 0 && $nb > 0) ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }

    /** Hashed bag of words and word pairs, L2-normalised. */
    public static function lexical(string $text): array
    {
        $vec = array_fill(0, self::DIMENSIONS, 0.0);
        $words = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [],
            fn ($w) => mb_strlen($w) > 2 && ! in_array($w, self::STOP, true)
        ));
        $terms = $words;
        for ($i = 1; $i < count($words); $i++) {
            $terms[] = $words[$i - 1].' '.$words[$i];
        }
        foreach ($terms as $term) {
            $h = crc32($term);
            $vec[$h % self::DIMENSIONS] += ($h & 1) ? 1.0 : -1.0;
        }
        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $vec)));

        return $norm > 0 ? array_map(fn ($x) => $x / $norm, $vec) : $vec;
    }

    private function queryVector(string $query, string $feature): array
    {
        $key = 'emb:q:'.hash('sha256', $this->llm->embeddingModel()."\x1f".$query);
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit;
        }
        $vector = $this->llm->embed($feature.'.query', [$query])[0] ?? [];
        if ($vector === []) {
            throw new AiException('The provider returned an empty embedding.');
        }
        Cache::put($key, $vector, now()->addDays(30));

        return $vector;
    }

    /**
     * Stored vectors by item key, embedding the missing ones (or, with $missingOnly, the items
     * that had to be embedded). More than $maxNew missing items is treated as a failure, so a
     * web request never embeds a whole catalogue: run games:embed instead.
     *
     * @return array<string|int, list<float>|array>
     */
    private function stored(array $items, string $feature, bool $missingOnly = false, int $maxNew = PHP_INT_MAX): array
    {
        $model = $this->llm->embeddingModel();
        $stored = collect();
        foreach (collect($items)->groupBy('type') as $type => $group) {
            $stored = $stored->merge(DB::table('embeddings')->where('model', $model)->where('owner_type', $type)
                ->whereIn('owner_id', $group->pluck('id')->all())->get()
                ->keyBy(fn ($row) => $row->owner_type.':'.$row->owner_id));
        }

        $vectors = [];
        $missing = [];
        foreach ($items as $key => $item) {
            $hash = hash('sha256', $item['text']);
            $row = $stored->get($item['type'].':'.$item['id']);
            if ($row && $row->text_hash === $hash) {
                $vectors[$key] = array_values(unpack('g*', $row->vector));
            } else {
                $missing[$key] = $item + ['hash' => $hash];
            }
        }

        if (count($missing) > $maxNew) {
            throw new AiException(count($missing).' items have no stored vector yet.');
        }
        if ($missing !== []) {
            $fresh = $this->llm->embed($feature, array_column($missing, 'text'));
            foreach (array_keys($missing) as $i => $key) {
                $item = $missing[$key];
                $vectors[$key] = $fresh[$i];
                DB::table('embeddings')->updateOrInsert(
                    ['owner_type' => $item['type'], 'owner_id' => $item['id'], 'model' => $model],
                    ['text_hash' => $item['hash'], 'vector' => pack('g*', ...$fresh[$i]), 'updated_at' => now(), 'created_at' => now()],
                );
            }
        }

        return $missingOnly ? $missing : $vectors;
    }

    private const STOP = [
        'the', 'and', 'for', 'with', 'who', 'can', 'are', 'you', 'your', 'our', 'his', 'her', 'she', 'him', 'has',
        'have', 'from', 'that', 'this', 'will', 'need', 'needs', 'looking', 'want', 'like', 'some', 'something',
        'game', 'games', 'play', 'playing', 'also', 'very', 'all', 'any', 'not', 'but', 'into', 'its', 'their',
        'them', 'they', 'what', 'which', 'where', 'when', 'more', 'most', 'run', 'runs',
    ];
}
