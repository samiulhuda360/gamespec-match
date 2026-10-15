<?php

namespace Tests\Unit;

use App\Ai\AiException;
use App\Ai\LlmClient;
use App\Ai\OpenAiCompatibleClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class OpenAiCompatibleClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_requests_a_schema_and_decodes_the_reply(): void
    {
        $this->fakeAi([['answer' => 42]]);

        $out = app(LlmClient::class)->json('test', 'system', 'user', ['type' => 'object'], 100);

        $this->assertSame(['answer' => 42], $out);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://ai.test/v1/chat/completions'
            && $r['temperature'] === 0
            && $r['response_format']['type'] === 'json_schema'
            && $r->hasHeader('Authorization', 'Bearer test-key'));
        $this->assertDatabaseHas('ai_calls', ['feature' => 'test', 'ok' => true, 'input_tokens' => 100, 'output_tokens' => 20]);
    }

    public function test_fenced_json_is_accepted(): void
    {
        $this->assertSame(['a' => 1], OpenAiCompatibleClient::decodeJson("Here you go:\n```json\n{\"a\": 1}\n```"));
        $this->assertNull(OpenAiCompatibleClient::decodeJson('no json here'));
    }

    public function test_invalid_json_gets_one_repair_turn(): void
    {
        $this->fakeAi(['Sorry, here is the data: title is X', ['title' => 'X']]);

        $this->assertSame(['title' => 'X'], app(LlmClient::class)->json('test', 's', 'u', ['type' => 'object']));
        Http::assertSentCount(2);
    }

    public function test_repeated_calls_come_from_the_cache(): void
    {
        $this->fakeAi([['n' => 1]]);
        $client = app(LlmClient::class);

        $client->json('test', 's', 'same question', ['type' => 'object']);
        $client->json('test', 's', 'same question', ['type' => 'object']);

        Http::assertSentCount(1);
        $this->assertSame(1, DB::table('ai_calls')->where('cached', true)->count());
    }

    public function test_server_errors_are_retried_then_raised(): void
    {
        Sleep::fake();
        $this->fakeAi([500, 500, 500]);

        try {
            app(LlmClient::class)->text('test', 's', 'u');
            $this->fail('Expected AiException');
        } catch (AiException) {
            Http::assertSentCount(3);
            $this->assertDatabaseHas('ai_calls', ['feature' => 'test', 'ok' => false, 'error' => 'HTTP 500']);
        }
    }

    public function test_embeddings_keep_the_input_order(): void
    {
        $this->fakeAi([], fn (array $inputs) => array_map(fn ($t) => [strlen($t), 1.0], $inputs));

        $vectors = app(LlmClient::class)->embed('test', ['a', 'bbb']);

        $this->assertSame([[1.0, 1.0], [3.0, 1.0]], $vectors);
    }
}
