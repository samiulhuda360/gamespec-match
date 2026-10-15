<?php

namespace App\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Chat completions and embeddings over the OpenAI-compatible HTTP API.
 *
 * - temperature 0 and a JSON schema for structured answers
 * - one repair turn when the reply is not valid JSON
 * - retries on 429/5xx and connection errors, with a hard timeout
 * - results cached by a hash of everything that went into the call
 * - every call logged to ai_calls (latency, tokens, errors)
 */
class OpenAiCompatibleClient implements LlmClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $chatModel,
        private readonly string $embeddingModel,
        private readonly int $timeout,
        private readonly int $cacheDays,
        private readonly CallLog $log,
    ) {
    }

    public function isLive(): bool
    {
        return true;
    }

    public function chatModel(): string
    {
        return $this->chatModel;
    }

    public function embeddingModel(): string
    {
        return $this->embeddingModel;
    }

    public function json(string $feature, string $system, string $user, array $schema, int $maxTokens = 800): array
    {
        $key = $this->cacheKey('json', $feature, $system, $user, json_encode($schema));
        if (is_array($hit = Cache::get($key))) {
            $this->log->record($feature, $this->chatModel, 0, true, cached: true);

            return $hit;
        }

        $body = [
            'model' => $this->chatModel,
            'temperature' => 0,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => ['name' => preg_replace('/[^a-z0-9_]/i', '_', $feature), 'schema' => $schema],
            ],
        ];

        $reply = $this->chat($feature, $body);
        $data = self::decodeJson($reply);
        if ($data === null) {
            // One repair turn: show the model its own answer and ask for the JSON only.
            $body['messages'][] = ['role' => 'assistant', 'content' => $reply];
            $body['messages'][] = ['role' => 'user', 'content' => 'That was not a valid JSON object. Reply with the JSON object only.'];
            $data = self::decodeJson($this->chat($feature.'.repair', $body));
        }
        if ($data === null) {
            throw new AiException('The model did not return valid JSON.');
        }

        Cache::put($key, $data, now()->addDays($this->cacheDays));

        return $data;
    }

    public function text(string $feature, string $system, string $user, int $maxTokens = 400): string
    {
        $key = $this->cacheKey('text', $feature, $system, $user);
        if (is_string($hit = Cache::get($key))) {
            $this->log->record($feature, $this->chatModel, 0, true, cached: true);

            return $hit;
        }

        $text = trim($this->chat($feature, [
            'model' => $this->chatModel,
            'temperature' => 0.3,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]));
        if ($text === '') {
            throw new AiException('The model returned an empty answer.');
        }

        Cache::put($key, $text, now()->addDays($this->cacheDays));

        return $text;
    }

    public function embed(string $feature, array $texts): array
    {
        $out = [];
        foreach (array_chunk(array_values($texts), 64) as $batch) {
            $response = $this->post($feature, $this->embeddingModel, '/embeddings', [
                'model' => $this->embeddingModel,
                'input' => $batch,
            ]);
            $rows = collect($response->json('data', []))->sortBy('index')->values();
            if ($rows->count() !== count($batch)) {
                throw new AiException('The provider returned the wrong number of embeddings.');
            }
            foreach ($rows as $row) {
                $out[] = array_map('floatval', $row['embedding'] ?? []);
            }
        }

        return $out;
    }

    /** Pull a JSON object out of a reply, tolerating ```json fences. */
    public static function decodeJson(string $reply): ?array
    {
        $reply = trim($reply);
        if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $reply, $m)) {
            $reply = $m[1];
        }
        $start = strpos($reply, '{');
        $end = strrpos($reply, '}');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }
        $data = json_decode(substr($reply, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }

    private function chat(string $feature, array $body): string
    {
        $response = $this->post($feature, $this->chatModel, '/chat/completions', $body);
        $content = $response->json('choices.0.message.content');
        if (! is_string($content)) {
            throw new AiException('The provider returned no message.');
        }

        return $content;
    }

    private function post(string $feature, string $model, string $path, array $body): Response
    {
        $started = hrtime(true);
        $elapsed = fn () => (int) ((hrtime(true) - $started) / 1_000_000);

        try {
            $response = $this->request()->post(rtrim($this->baseUrl, '/').$path, $body);
        } catch (Throwable $e) {
            $this->log->record($feature, $model, $elapsed(), false, class_basename($e));
            throw new AiException('Could not reach the AI provider.', previous: $e);
        }

        if ($response->failed()) {
            $this->log->record($feature, $model, $elapsed(), false, 'HTTP '.$response->status());
            throw new AiException('The AI provider returned HTTP '.$response->status().'.');
        }

        $this->log->record($feature, $model, $elapsed(), true, null,
            $response->json('usage.prompt_tokens'), $response->json('usage.completion_tokens'));

        return $response;
    }

    private function request(): PendingRequest
    {
        $retryable = fn (Throwable $e) => $e instanceof ConnectionException
            || ($e instanceof RequestException && in_array($e->response->status(), [429, 500, 502, 503, 504], true));

        return Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry(3, 700, $retryable, throw: false);
    }

    private function cacheKey(string ...$parts): string
    {
        return 'ai:'.hash('sha256', $this->chatModel."\x1f".implode("\x1f", $parts));
    }
}
