<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class GroqProvider implements AiProviderInterface
{
    use HandlesVisionInput;

    private string $activeModel;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction',
        private readonly ?string $visionModel = null,
    ) {
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
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'      => $this->activeModel,
                'max_tokens' => 512,
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt . "\n\nResponde ÚNICAMENTE con JSON válido."],
                    ['role' => 'user',   'content' => $userContent],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Groq API error: ' . $response->body());
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
        $groqMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $messages)
        );

        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_creation_tokens' => 0];

        // OWF-379: antes usaba CURLOPT_WRITEFUNCTION con CURLOPT_RETURNTRANSFER=false para
        // streaming real. En respuestas de error (ej. modelo eliminado/sin acceso), libcurl
        // deja de invocar el WRITEFUNCTION para ese body (reproducido: fwrite de diagnóstico
        // dentro del callback nunca se ejecuta) y el body crudo del proveedor (JSON de error,
        // a veces con URLs internas del proveedor) se escribe DIRECTO al output buffer de
        // PHP — que en un request HTTP real ES la respuesta al cliente. Con RETURNTRANSFER
        // en true no hay output buffer involucrado: el body siempre vuelve como string, se
        // valida el HTTP code ANTES de tocarlo, y solo entonces se parsea. Se pierde el
        // streaming token-a-token real (los deltas se emiten en un loop apenas llega la
        // respuesta completa, no a medida que el modelo genera) — trade-off aceptado: nunca
        // más se filtra un body de error crudo al usuario.
        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => 'https://api.groq.com/openai/v1/chat/completions',
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->apiKey}", 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'model'    => $this->advisorModel,
                'stream'   => true,
                'messages' => $groqMessages,
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $rawOutput = curl_exec($curlHandle);
        $httpCode  = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlErr   = curl_errno($curlHandle) ? curl_error($curlHandle) : null;
        curl_close($curlHandle);

        if ($curlErr) {
            throw new \RuntimeException("Groq streamChat transport error: {$curlErr}");
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Groq streamChat HTTP {$httpCode}: " . substr((string) $rawOutput, 0, 300));
        }

        foreach (explode("\n", (string) $rawOutput) as $line) {
            if (!str_starts_with($line, 'data: ') || trim($line) === 'data: [DONE]') continue;
            $json = json_decode(substr($line, 6), true);
            if (!$json) continue;
            $text = $json['choices'][0]['delta']['content'] ?? '';
            if ($text) $onDelta($text);
            if (isset($json['x_groq']['usage'])) {
                $usage['input_tokens']  = $json['x_groq']['usage']['prompt_tokens'] ?? 0;
                $usage['output_tokens'] = $json['x_groq']['usage']['completion_tokens'] ?? 0;
            }
        }

        return ['usage' => $usage, 'model' => $this->advisorModel];
    }

    public function name(): string { return 'groq'; }

    public function model(): string
    {
        if ($this->feature === 'advisor') {
            return $this->advisorModel;
        }
        return $this->activeModel;
    }
}
