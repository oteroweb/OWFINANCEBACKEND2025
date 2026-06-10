<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAiProvider;
use App\Services\AI\Providers\GroqProvider;
use InvalidArgumentException;

class AiProviderFactory
{
    /**
     * @param string $feature 'extraction' | 'advisor'
     */
    public static function make(string $feature): AiProviderInterface
    {
        $providerName = config("ai.features.{$feature}", 'anthropic');
        $cfg          = config("ai.providers.{$providerName}");

        if (!$cfg || empty($cfg['key'])) {
            throw new \RuntimeException(
                "AI provider '{$providerName}' is not configured. Set the API key in .env."
            );
        }

        $extractionModel = $cfg['models']['extraction'];
        $advisorModel    = $cfg['models']['advisor'];

        return match ($providerName) {
            'anthropic' => new AnthropicProvider($cfg['key'], $extractionModel, $advisorModel, $feature),
            'gemini'    => new GeminiProvider($cfg['key'], $extractionModel, $advisorModel, $feature),
            'openai'    => new OpenAiProvider($cfg['key'], $extractionModel, $advisorModel, $feature),
            'groq'      => new GroqProvider($cfg['key'], $extractionModel, $advisorModel, $feature),
            default     => throw new InvalidArgumentException("Unknown AI provider: {$providerName}"),
        };
    }

    /** Cost in USD for given usage + provider */
    public static function estimateCost(string $providerName, array $usage): float
    {
        $pricing = config("ai.providers.{$providerName}.pricing", []);

        return (($usage['input_tokens'] ?? 0) / 1_000_000) * ($pricing['input'] ?? 0)
             + (($usage['output_tokens'] ?? 0) / 1_000_000) * ($pricing['output'] ?? 0)
             + (($usage['cache_read_tokens'] ?? 0) / 1_000_000) * ($pricing['cache_read'] ?? 0)
             + (($usage['cache_creation_tokens'] ?? 0) / 1_000_000) * ($pricing['cache_write'] ?? 0);
    }
}
