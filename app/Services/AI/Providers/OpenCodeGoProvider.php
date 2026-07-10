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

    public function name(): string { return 'opencode-go'; }

    public function model(): string
    {
        if ($this->feature === 'advisor') {
            return $this->advisorModel;
        }
        return $this->activeModel;
    }
}
