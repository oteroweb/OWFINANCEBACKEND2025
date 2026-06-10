<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $extractionModel,
        private readonly string $advisorModel,
        private readonly string $feature = 'extraction'
    ) {}

    public function extract(string $systemPrompt, array $userMessage): array
    {
        $model = $this->extractionModel;
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

        // Convert userMessage (Anthropic format) to Gemini parts format
        $parts = is_array($userMessage)
            ? array_map(function ($m) {
                if (isset($m['source'])) {
                    return ['inline_data' => ['mime_type' => 'image/jpeg', 'data' => $m['source']['data']]];
                }
                return ['text' => $m['text'] ?? ''];
            }, $userMessage)
            : [['text' => $userMessage]];

        $response = Http::timeout(30)->post($url, [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents'           => [['role' => 'user', 'parts' => $parts]],
            'generationConfig'   => ['maxOutputTokens' => 512, 'responseMimeType' => 'application/json'],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $data    = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $meta    = $data['usageMetadata'] ?? [];

        return [
            'content' => $content,
            'usage'   => [
                'input_tokens'          => $meta['promptTokenCount'] ?? 0,
                'output_tokens'         => $meta['candidatesTokenCount'] ?? 0,
                'cache_read_tokens'     => 0,
                'cache_creation_tokens' => 0,
            ],
            'model' => $model,
        ];
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        $model = $this->advisorModel;
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?key={$this->apiKey}&alt=sse";

        // Convert Anthropic messages format to Gemini format
        $contents = array_map(fn($m) => [
            'role'  => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_creation_tokens' => 0];

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents'           => $contents,
                'generationConfig'   => ['maxOutputTokens' => 1024],
            ]),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($onDelta, &$usage) {
                foreach (explode("\n", $data) as $line) {
                    if (!str_starts_with($line, 'data: ')) continue;
                    $json = json_decode(substr($line, 6), true);
                    if (!$json) continue;
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if ($text) $onDelta($text);
                    if (isset($json['usageMetadata'])) {
                        $usage['input_tokens']  += $json['usageMetadata']['promptTokenCount'] ?? 0;
                        $usage['output_tokens'] += $json['usageMetadata']['candidatesTokenCount'] ?? 0;
                    }
                }
                return strlen($data);
            },
            CURLOPT_RETURNTRANSFER => false,
        ]);
        curl_exec($curlHandle);
        curl_close($curlHandle);

        return ['usage' => $usage, 'model' => $model];
    }

    public function name(): string { return 'gemini'; }

    public function model(): string
    {
        return $this->feature === 'advisor' ? $this->advisorModel : $this->extractionModel;
    }
}
