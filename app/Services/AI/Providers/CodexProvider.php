<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

/**
 * OWF-383: modelos Codex de OpenAI (gpt-5-codex, codex-mini-latest...). A diferencia del
 * resto de modelos de OpenAI, los Codex SOLO existen en la Responses API (`/v1/responses`),
 * no en `/v1/chat/completions` — por eso no se reutiliza OpenAiProvider.
 *
 * streamChat() hace una llamada no-streaming y reemite el texto en trozos: mismo criterio
 * que OWF-379 (nunca más WRITEFUNCTION de cURL, que filtra bodies de error al cliente).
 */
class CodexProvider implements AiProviderInterface
{
    use HandlesVisionInput;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction',
        private readonly string $baseUrl = 'https://api.openai.com/v1',
        private readonly string $reasoningEffort = 'low',
    ) {}

    public function extract(string $systemPrompt, array $userMessage): array
    {
        // Los modelos Codex no son la vía para OCR/visión: se rechaza para que
        // AiProviderChain caiga al siguiente proveedor (que sí soporta imágenes).
        if ($this->hasVisionContent($userMessage)) {
            throw new \RuntimeException('Codex provider does not handle image input');
        }

        $userContent = implode(' ', array_column(
            array_filter($userMessage, fn($m) => isset($m['text'])),
            'text'
        ));

        $data = $this->call(
            $this->extractionModel,
            $systemPrompt . "\n\nResponde ÚNICAMENTE con JSON válido.",
            [['role' => 'user', 'content' => $userContent]],
            1024
        );

        return [
            'content' => $this->outputText($data) ?: '{}',
            'usage'   => $this->usage($data),
            'model'   => $this->extractionModel,
        ];
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        $input = array_map(
            fn($m) => ['role' => $m['role'] === 'assistant' ? 'assistant' : 'user', 'content' => (string) $m['content']],
            $messages
        );

        $data = $this->call($this->advisorModel, $systemPrompt, $input, 2048);

        $text = $this->outputText($data);
        if ($text === '') {
            throw new \RuntimeException('Codex returned an empty response');
        }

        foreach (mb_str_split($text, 48) as $chunk) {
            $onDelta($chunk);
        }

        return ['usage' => $this->usage($data), 'model' => $this->advisorModel];
    }

    public function name(): string { return 'codex'; }

    public function model(): string
    {
        return $this->feature === 'advisor' ? $this->advisorModel : $this->extractionModel;
    }

    private function call(string $model, string $instructions, array $input, int $maxOutputTokens): array
    {
        $response = Http::withHeaders(['Authorization' => "Bearer {$this->apiKey}", 'Content-Type' => 'application/json'])
            ->timeout(60)
            ->post("{$this->baseUrl}/responses", [
                'model'             => $model,
                'instructions'      => $instructions,
                'input'             => $input,
                'max_output_tokens' => $maxOutputTokens,
                'reasoning'         => ['effort' => $this->reasoningEffort],
                'store'             => false,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Codex API HTTP {$response->status()}: " . substr($response->body(), 0, 300));
        }

        return $response->json() ?? [];
    }

    /** Concatena los `output_text` de los items `message` (ignora items `reasoning`). */
    private function outputText(array $data): string
    {
        $text = '';
        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') continue;
            foreach ($item['content'] ?? [] as $part) {
                if (($part['type'] ?? null) === 'output_text') {
                    $text .= $part['text'] ?? '';
                }
            }
        }
        return trim($text);
    }

    private function usage(array $data): array
    {
        $u = $data['usage'] ?? [];
        return [
            'input_tokens'          => $u['input_tokens'] ?? 0,
            'output_tokens'         => $u['output_tokens'] ?? 0,
            'cache_read_tokens'     => $u['input_tokens_details']['cached_tokens'] ?? 0,
            'cache_creation_tokens' => 0,
        ];
    }
}
