<?php

namespace App\Services\AI\Contracts;

interface AiProviderInterface
{
    /**
     * Send a single extraction request (non-streaming).
     * Returns ['content' => string, 'usage' => [...], 'model' => string]
     */
    public function extract(string $systemPrompt, array $userMessage): array;

    /**
     * Stream a chat response via SSE.
     * Calls $onDelta(string $text) for each chunk.
     * Returns ['usage' => [...], 'model' => string]
     */
    public function streamChat(
        string $systemPrompt,
        array  $messages,
        callable $onDelta
    ): array;

    /** Provider name for logging */
    public function name(): string;

    /** Model being used for this feature */
    public function model(): string;
}
