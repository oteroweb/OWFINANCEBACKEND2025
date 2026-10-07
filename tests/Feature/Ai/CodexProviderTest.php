<?php

namespace Tests\Feature\Ai;

use App\Services\AI\AiProviderChain;
use App\Services\AI\AiProviderFactory;
use App\Services\AI\Providers\CodexProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * OWF-383: provider Codex (OpenAI Responses API) para el Asesor IA.
 */
class CodexProviderTest extends TestCase
{
    private function provider(): CodexProvider
    {
        return new CodexProvider('sk-test', 'gpt-5-codex', 'gpt-5-codex', 'advisor');
    }

    private function responsesPayload(string $text): array
    {
        return [
            'output' => [
                ['type' => 'reasoning', 'summary' => []],
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => $text]]],
            ],
            'usage' => ['input_tokens' => 12, 'output_tokens' => 7, 'input_tokens_details' => ['cached_tokens' => 3]],
        ];
    }

    public function test_stream_chat_emits_message_text_ignoring_reasoning_items(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->responsesPayload('Hola, tu balance este mes es positivo.'), 200)]);

        $deltas = [];
        $result = $this->provider()->streamChat('Eres un asesor.', [['role' => 'user', 'content' => 'hola']], function ($t) use (&$deltas) {
            $deltas[] = $t;
        });

        $this->assertSame('Hola, tu balance este mes es positivo.', implode('', $deltas));
        $this->assertSame(12, $result['usage']['input_tokens']);
        $this->assertSame(3, $result['usage']['cache_read_tokens']);
        $this->assertSame('gpt-5-codex', $result['model']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer sk-test')
                && $request['model'] === 'gpt-5-codex'
                && $request['instructions'] === 'Eres un asesor.'
                && $request['input'][0] === ['role' => 'user', 'content' => 'hola']
                && $request['store'] === false;
        });
    }

    public function test_http_error_throws_instead_of_leaking_body(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response(['error' => ['message' => 'model_not_found']], 404)]);

        $deltas = [];
        try {
            $this->provider()->streamChat('x', [['role' => 'user', 'content' => 'hola']], function ($t) use (&$deltas) {
                $deltas[] = $t;
            });
            $this->fail('expected exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Codex API HTTP 404', $e->getMessage());
        }
        $this->assertSame([], $deltas);
    }

    public function test_empty_output_throws_so_chain_can_fall_back(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response(['output' => [['type' => 'reasoning']]], 200)]);

        $this->expectException(\RuntimeException::class);
        $this->provider()->streamChat('x', [['role' => 'user', 'content' => 'hola']], fn() => null);
    }

    public function test_extract_returns_json_content_and_rejects_images(): void
    {
        Http::fake(['api.openai.com/v1/responses' => Http::response($this->responsesPayload('{"amount":15}'), 200)]);

        $res = $this->provider()->extract('sys', [['text' => 'gasté 15']]);
        $this->assertSame('{"amount":15}', $res['content']);

        $this->expectException(\RuntimeException::class);
        $this->provider()->extract('sys', [['type' => 'image', 'source' => ['media_type' => 'image/jpeg', 'data' => 'AAAA']], ['type' => 'text', 'text' => 'ticket']]);
    }

    public function test_chain_falls_back_from_failing_codex_to_next_provider(): void
    {
        Http::fake([
            'api.openai.com/v1/responses' => Http::response('boom', 500),
        ]);

        $fallback = new class implements \App\Services\AI\Contracts\AiProviderInterface {
            public function extract(string $s, array $u): array { return []; }
            public function streamChat(string $s, array $m, callable $onDelta): array { $onDelta('ok-fallback'); return ['usage' => [], 'model' => 'fb']; }
            public function name(): string { return 'fb'; }
            public function model(): string { return 'fb'; }
        };

        $chain  = new AiProviderChain([$this->provider(), $fallback]);
        $out    = '';
        $result = $chain->streamChat('x', [['role' => 'user', 'content' => 'hola']], function ($t) use (&$out) { $out .= $t; });

        $this->assertSame('ok-fallback', $out);
        $this->assertSame('fb', $result['provider']);
    }

    public function test_factory_builds_codex_first_when_configured_as_advisor_primary(): void
    {
        // El .env real puede fijar AI_ADVISOR_PROVIDER (pisa el default de config/ai.php),
        // así que el test fija la config explícitamente en vez de depender del entorno.
        config([
            'ai.features.advisor'          => 'codex',
            'ai.features.advisor_fallback' => 'opencode-go',
            'ai.providers.codex.key'       => 'sk-test',
            'ai.providers.opencode-go.key' => 'k2',
        ]);

        $chain = AiProviderFactory::makeWithRuntimeFallback('advisor');
        $this->assertStringStartsWith('codex+', $chain->name());
        $this->assertSame('gpt-5-codex', $chain->model());
    }
}
