<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction'
    ) {}

    public function extract(string $systemPrompt, array $userMessage): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->extractionModel,
                'max_tokens' => 512,
                'system'     => [
                    ['type' => 'text', 'text' => $systemPrompt, 'cache_control' => ['type' => 'ephemeral']],
                ],
                'messages' => [['role' => 'user', 'content' => $userMessage]],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'content' => $data['content'][0]['text'] ?? '{}',
            'usage'   => $this->normalizeUsage($data['usage'] ?? []),
            'model'   => $this->extractionModel,
        ];
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        $usage = [];

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => 'https://api.anthropic.com/v1/messages',
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => $this->curlHeaders(),
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => $this->advisorModel,
                'max_tokens' => 1024,
                'stream'     => true,
                'system'     => [
                    ['type' => 'text', 'text' => $systemPrompt, 'cache_control' => ['type' => 'ephemeral']],
                ],
                'messages' => $messages,
            ]),
            CURLOPT_WRITEFUNCTION  => function ($ch, $data) use ($onDelta, &$usage) {
                foreach (explode("\n", $data) as $line) {
                    if (!str_starts_with($line, 'data: ')) continue;
                    $json = json_decode(substr($line, 6), true);
                    if (!$json) continue;
                    if ($json['type'] === 'content_block_delta') $onDelta($json['delta']['text'] ?? '');
                    if ($json['type'] === 'message_delta') $usage = array_merge($usage, $json['usage'] ?? []);
                    if ($json['type'] === 'message_start') $usage = array_merge($usage, $json['message']['usage'] ?? []);
                }
                return strlen($data);
            },
            CURLOPT_RETURNTRANSFER => false,
        ]);
        curl_exec($curlHandle);
        curl_close($curlHandle);

        return ['usage' => $this->normalizeUsage($usage), 'model' => $this->advisorModel];
    }

    public function name(): string { return 'anthropic'; }

    public function model(): string
    {
        return $this->feature === 'advisor' ? $this->advisorModel : $this->extractionModel;
    }

    private function headers(): array
    {
        return [
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
            'anthropic-beta'    => 'prompt-caching-2024-07-31',
        ];
    }

    private function curlHeaders(): array
    {
        return array_map(
            fn($k, $v) => "$k: $v",
            array_keys($this->headers()),
            array_values($this->headers())
        );
    }

    private function normalizeUsage(array $raw): array
    {
        return [
            'input_tokens'          => $raw['input_tokens'] ?? 0,
            'output_tokens'         => $raw['output_tokens'] ?? 0,
            'cache_read_tokens'     => $raw['cache_read_input_tokens'] ?? 0,
            'cache_creation_tokens' => $raw['cache_creation_input_tokens'] ?? 0,
        ];
    }
}
