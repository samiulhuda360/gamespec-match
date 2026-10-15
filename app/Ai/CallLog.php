<?php

namespace App\Ai;

use Illuminate\Support\Facades\DB;
use Throwable;

/** Writes one ai_calls row per model call. Logging must never break the feature. */
class CallLog
{
    public function record(string $feature, string $model, int $latencyMs, bool $ok, ?string $error = null,
        ?int $inputTokens = null, ?int $outputTokens = null, bool $cached = false): void
    {
        try {
            DB::table('ai_calls')->insert([
                'feature' => mb_substr($feature, 0, 40),
                'model' => mb_substr($model, 0, 80),
                'latency_ms' => max(0, $latencyMs),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'ok' => $ok,
                'cached' => $cached,
                'error' => $error === null ? null : mb_substr($error, 0, 255),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // The table may not exist yet (fresh install); the call itself already happened.
        }
    }
}
