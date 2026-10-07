<?php

namespace Tests\Feature\Ai;

use App\Services\AI\Providers\OpenCodeGoProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * OWF-385: OpenCode Zen/Go responde 400 MissingSessionID a toda request sin el header
 * x-opencode-session. Cada llamada debe enviar uno (UUID).
 */
class OpenCodeGoSessionHeaderTest extends TestCase
{
    public function test_extract_sends_x_opencode_session_uuid_header(): void
    {
        Http::fake(['opencode.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"ok":true}']]],
            'usage'   => ['prompt_tokens' => 1, 'completion_tokens' => 1],
        ], 200)]);

        $p = new OpenCodeGoProvider('k', 'glm-5.1', 'glm-5.1', 'extraction');
        $p->extract('sys', [['type' => 'text', 'text' => 'hola']]);
        $p->extract('sys', [['type' => 'text', 'text' => 'hola']]);

        $seen = [];
        Http::assertSentCount(2);
        Http::assertSent(function ($request) use (&$seen) {
            $sid = $request->header('x-opencode-session')[0] ?? '';
            $seen[] = $sid;
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $sid) === 1;
        });
        $this->assertCount(2, array_unique($seen), 'cada llamada usa un session id propio');
    }
}
