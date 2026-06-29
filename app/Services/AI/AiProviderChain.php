<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProviderInterface;

/**
 * Wraps multiple providers and tries them in order on failure.
 * Extraction calls retry on exception. Streaming uses first provider only
 * (mid-stream restart is not feasible with SSE).
 */
class AiProviderChain implements AiProviderInterface
{
    public function __construct(private readonly array $providers) {}

    public function extract(string $systemPrompt, array $userMessage): array
    {
        $lastError = null;

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->extract($systemPrompt, $userMessage);
                // Tag which provider actually responded
                $result['provider'] = $provider->name();
                return $result;
            } catch (\Throwable $e) {
                \Log::warning("AI chain: provider '{$provider->name()}' failed extraction: " . $e->getMessage());
                $lastError = $e;
            }
        }

        throw new \RuntimeException(
            'All AI providers failed for extraction. Last error: ' . ($lastError?->getMessage() ?? 'unknown')
        );
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        // Try streaming with each provider; if the first fails before producing output, try next
        $lastError = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->streamChat($systemPrompt, $messages, $onDelta);
            } catch (\Throwable $e) {
                \Log::warning("AI chain: provider '{$provider->name()}' failed streamChat: " . $e->getMessage());
                $lastError = $e;
            }
        }

        throw new \RuntimeException(
            'All AI providers failed for chat. Last error: ' . ($lastError?->getMessage() ?? 'unknown')
        );
    }

    public function name(): string
    {
        $names = array_map(fn($p) => $p->name(), $this->providers);
        return implode('+', $names);
    }

    public function model(): string
    {
        return $this->providers[0]->model();
    }
}
