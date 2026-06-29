<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class XaiProvider implements AiProviderInterface
{
    private string $baseUrl = 'https://api.x.ai/v1';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction'
    ) {}

    public function extract(string $systemPrompt, array $userMessage): array
    {
        $text = is_array($userMessage)
            ? implode(' ', array_column(array_filter($userMessage, fn($m) => isset($m['text'])), 'text'))
            : $userMessage;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'      => $this->extractionModel,
                'max_tokens' => 1024,
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt . "\n\nResponde ÚNICAMENTE con JSON válido."],
                    ['role' => 'user',   'content' => $text],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('xAI API error: ' . $response->body());
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
            'model' => $this->extractionModel,
        ];
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        $outMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $messages)
        );

        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_creation_tokens' => 0];

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => "{$this->baseUrl}/chat/completions",
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->apiKey}",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'    => $this->advisorModel,
                'stream'   => true,
                'messages' => $outMessages,
            ]),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($onDelta, &$usage) {
                foreach (explode("\n", $data) as $line) {
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
                return strlen($data);
            },
            CURLOPT_RETURNTRANSFER => false,
        ]);
        curl_exec($curlHandle);
        curl_close($curlHandle);

        return ['usage' => $usage, 'model' => $this->advisorModel];
    }

    public function name(): string { return 'xai'; }

    public function model(): string
    {
        return $this->feature === 'advisor' ? $this->advisorModel : $this->extractionModel;
    }
}
