<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction'
    ) {}

    public function extract(string $systemPrompt, array $userMessage): array
    {
        $content = is_array($userMessage)
            ? array_map(function ($m) {
                if (isset($m['source'])) {
                    return ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$m['source']['data']}"]];
                }
                return ['type' => 'text', 'text' => $m['text'] ?? ''];
            }, $userMessage)
            : [['type' => 'text', 'text' => $userMessage]];

        $response = Http::withHeaders(['Authorization' => "Bearer {$this->apiKey}", 'Content-Type' => 'application/json'])
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $this->extractionModel,
                'max_tokens'  => 512,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $content],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: ' . $response->body());
        }

        $data  = $response->json();
        $usage = $data['usage'] ?? [];

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '{}',
            'usage'   => [
                'input_tokens'          => $usage['prompt_tokens'] ?? 0,
                'output_tokens'         => $usage['completion_tokens'] ?? 0,
                'cache_read_tokens'     => $usage['prompt_tokens_details']['cached_tokens'] ?? 0,
                'cache_creation_tokens' => 0,
            ],
            'model' => $this->extractionModel,
        ];
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        $openAiMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $messages)
        );

        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_creation_tokens' => 0];

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->apiKey}", 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'model'          => $this->advisorModel,
                'max_tokens'     => 1024,
                'stream'         => true,
                'stream_options' => ['include_usage' => true],
                'messages'       => $openAiMessages,
            ]),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($onDelta, &$usage) {
                foreach (explode("\n", $data) as $line) {
                    if (!str_starts_with($line, 'data: ') || trim($line) === 'data: [DONE]') continue;
                    $json = json_decode(substr($line, 6), true);
                    if (!$json) continue;
                    $text = $json['choices'][0]['delta']['content'] ?? '';
                    if ($text) $onDelta($text);
                    if (isset($json['usage'])) {
                        $usage['input_tokens']      = $json['usage']['prompt_tokens'] ?? 0;
                        $usage['output_tokens']     = $json['usage']['completion_tokens'] ?? 0;
                        $usage['cache_read_tokens'] = $json['usage']['prompt_tokens_details']['cached_tokens'] ?? 0;
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

    public function name(): string { return 'openai'; }

    public function model(): string
    {
        return $this->feature === 'advisor' ? $this->advisorModel : $this->extractionModel;
    }
}
