<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class OpenCodeGoProvider implements AiProviderInterface
{
    use HandlesVisionInput;

    private string $baseUrl;
    private string $activeModel;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction',
        private readonly ?string $visionModel = null,
    ) {
        $this->baseUrl    = config('ai.providers.opencode-go.base_url', 'https://opencode.ai/zen/go/v1');
        $this->activeModel = $extractionModel;
    }

    public function extract(string $systemPrompt, array $userMessage): array
    {
        $useVision = $this->visionModel && $this->hasVisionContent($userMessage);

        if ($useVision) {
            $this->activeModel = $this->visionModel;
            $userContent = $this->toOpenAiVisionContent($userMessage);
        } else {
            $this->activeModel = $this->extractionModel;
            $userContent = implode(' ', array_column(
                array_filter($userMessage, fn($m) => isset($m['text'])),
                'text'
            ));
        }

        $response = Http::withHeaders(['Authorization' => "Bearer {$this->apiKey}", 'Content-Type' => 'application/json'])
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'      => $this->activeModel,
                'max_tokens' => 1024,
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt . "\n\nResponde ÚNICAMENTE con JSON válido."],
                    ['role' => 'user',   'content' => $userContent],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenCode Go API error: ' . $response->body());
        }

        $data  = $response->json();
        $usage = $data['usage'] ?? [];

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '{}',
            'usage'   => [
                'input_tokens'          => $usage['prompt_tokens'] ?? 0,
                'output_tokens'         => $usage['completion_tokens'] ?? 0,
                'cache_read_tokens'     => 0,
                'cache_creation_tokens' => 0,
            ],
            'model' => $this->activeModel,
        ];
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        $outMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $messages)
        );

        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_creation_tokens' => 0];

        // OWF-379: el fix de OWF-310 (validar HTTP code) no alcanzaba — seguía usando
        // CURLOPT_WRITEFUNCTION con RETURNTRANSFER=false, y en respuestas de error libcurl
        // deja de invocar el WRITEFUNCTION (reproducido en vivo: un callback de diagnóstico
        // adentro nunca se ejecutaba) y escribe el body crudo del proveedor DIRECTO al
        // output buffer de PHP — que en un request real ES la respuesta HTTP al cliente
        // (así se filtró la URL interna de opt-in de OpenCode Zen a un usuario real). Con
        // RETURNTRANSFER en true no hay output buffer de por medio: el body siempre vuelve
        // como string, se valida el HTTP code ANTES de tocarlo, recién entonces se parsea.
        // Se pierde el streaming token-a-token real (los deltas se emiten en loop apenas
        // llega la respuesta completa) — trade-off aceptado por seguridad.
        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => "{$this->baseUrl}/chat/completions",
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->apiKey}", 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'model'    => $this->advisorModel,
                'stream'   => true,
                'messages' => $outMessages,
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $rawOutput = curl_exec($curlHandle);
        $httpCode  = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlErr   = curl_errno($curlHandle) ? curl_error($curlHandle) : null;
        curl_close($curlHandle);

        if ($curlErr) {
            throw new \RuntimeException("OpenCode Go streamChat transport error: {$curlErr}");
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("OpenCode Go streamChat HTTP {$httpCode}: " . substr((string) $rawOutput, 0, 300));
        }

        foreach (explode("\n", (string) $rawOutput) as $line) {
            if (!str_starts_with($line, 'data: ') || trim($line) === 'data: [DONE]') continue;
            $json = json_decode(substr($line, 6), true);
            if (!$json) continue;
            $text = $json['choices'][0]['delta']['content'] ?? '';
            if ($text) $onDelta($text);
            if (isset($json['usage'])) {
                $usage['input_tokens']  = $json['usage']['prompt_tokens'] ?? 0;
                $usage['output_tokens'] = $json['usage']['completion_tokens'] ?? 0;
            }
        }

        return ['usage' => $usage, 'model' => $this->advisorModel];
    }

    public function name(): string { return 'opencode-go'; }

    public function model(): string
    {
        if ($this->feature === 'advisor') {
            return $this->advisorModel;
        }
        return $this->activeModel;
    }
}
