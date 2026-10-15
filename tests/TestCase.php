<?php

namespace Tests;

use App\Ai\CallLog;
use App\Ai\LlmClient;
use App\Ai\OpenAiCompatibleClient;
use App\Models\CPU;
use App\Models\GPU;
use App\Models\UserHardware;
use App\Services\HardwareScorer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test database starts from the demo seed: the hardware catalogue, the 153-game
     * sample catalogue (scored on import), the demo user and the collections.
     */
    protected $seed = true;

    /** A scored hardware profile owned by $userId, or by the session id given. */
    protected function pc(string $cpu, string $gpu, int $ramGb = 16, ?int $userId = null, ?string $sessionId = 'someone-else'): UserHardware
    {
        $c = CPU::where('name', $cpu)->firstOrFail();
        $g = GPU::where('name', $gpu)->firstOrFail();
        $pc = new UserHardware([
            'profile_name' => 'Test PC', 'cpu_id' => $c->id, 'cpu_model' => $c->name,
            'gpu_id' => $g->id, 'gpu_model' => $g->name, 'ram_gb' => $ramGb, 'platform' => 'windows_11',
        ]);
        $pc->user_id = $userId;
        $pc->session_id = $userId ? null : $sessionId;
        app(HardwareScorer::class)->scoreHardware($pc);

        return $pc;
    }

    /**
     * Swap in the real OpenAI-compatible client, pointed at a fake provider.
     *
     * @param  list<array|string|int>  $chat  replies in order: an array is sent as the JSON message
     *                                        content, a string as plain text, an int as an HTTP error status
     * @param  (callable(list<string>): list<list<float>>)|null  $embed
     */
    protected function fakeAi(array $chat = [], ?callable $embed = null): void
    {
        Http::fake(function (Request $request) use (&$chat, $embed) {
            if (str_ends_with($request->url(), '/embeddings')) {
                $vectors = $embed ? $embed($request['input']) : array_map(fn () => [1.0, 0.0], $request['input']);

                return Http::response(['data' => array_map(fn ($v, $i) => ['index' => $i, 'embedding' => $v], $vectors, array_keys($vectors))]);
            }
            $next = array_shift($chat);
            if (is_int($next)) {
                return Http::response(['error' => 'fake'], $next);
            }
            $content = is_array($next) ? json_encode($next) : (string) $next;

            return Http::response([
                'choices' => [['message' => ['content' => $content]]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20],
            ]);
        });

        $this->app->instance(LlmClient::class, new OpenAiCompatibleClient(
            'https://ai.test/v1', 'test-key', 'fake-chat', 'fake-embed', 5, 1, new CallLog,
        ));
    }
}
