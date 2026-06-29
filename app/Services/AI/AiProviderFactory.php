<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAiProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\AI\Providers\OpenCodeGoProvider;
use App\Services\AI\Providers\OpenRouterProvider;
use App\Services\AI\Providers\XaiProvider;
use InvalidArgumentException;

class AiProviderFactory
{
    /**
     * Returns the first configured provider in the fallback chain for a feature.
     * Chain order: primary → fallback_1 → fallback_2 → ... → throws if none configured.
     *
     * @param string $feature 'extraction' | 'advisor'
     */
    public static function make(string $feature): AiProviderInterface
    {
        $primary  = config("ai.features.{$feature}", 'opencode-go');
        $fallback = array_filter(
            array_map('trim', explode(',', config("ai.features.{$feature}_fallback", ''))),
            fn($p) => !empty($p)
        );

        $chain = array_unique([$primary, ...$fallback]);

        foreach ($chain as $providerName) {
            $cfg = config("ai.providers.{$providerName}");
            if (!$cfg || empty($cfg['key'])) {
                continue; // no key configured, skip silently
            }

            try {
                return self::build($providerName, $cfg, $feature);
            } catch (\Throwable $e) {
                \Log::warning("AI provider '{$providerName}' failed to instantiate: " . $e->getMessage());
            }
        }

        throw new \RuntimeException(
            "No AI provider configured for feature '{$feature}'. " .
            "Set at least one API key in .env (AI_{PROVIDER}_API_KEY)."
        );
    }

    /**
     * Try providers in chain and return the first that succeeds for extraction.
     * On API call failure, automatically falls back to the next provider.
     *
     * @param string $feature 'extraction' | 'advisor'
     */
    public static function makeWithRuntimeFallback(string $feature): AiProviderInterface
    {
        $primary  = config("ai.features.{$feature}", 'opencode-go');
        $fallback = array_filter(
            array_map('trim', explode(',', config("ai.features.{$feature}_fallback", ''))),
            fn($p) => !empty($p)
        );

        $chain = array_unique([$primary, ...$fallback]);
        $providers = [];

        foreach ($chain as $providerName) {
            $cfg = config("ai.providers.{$providerName}");
            if ($cfg && !empty($cfg['key'])) {
                try {
                    $providers[] = self::build($providerName, $cfg, $feature);
                } catch (\Throwable) {
                    // skip
                }
            }
        }

        if (empty($providers)) {
            throw new \RuntimeException("No AI provider configured for feature '{$feature}'.");
        }

        return new AiProviderChain($providers);
    }

    private static function build(string $providerName, array $cfg, string $feature): AiProviderInterface
    {
        $em = $cfg['models']['extraction'];
        $am = $cfg['models']['advisor'];

        return match ($providerName) {
            'anthropic'   => new AnthropicProvider($cfg['key'], $em, $am, $feature),
            'gemini'      => new GeminiProvider($cfg['key'], $em, $am, $feature),
            'openai'      => new OpenAiProvider($cfg['key'], $em, $am, $feature),
            'groq'        => new GroqProvider($cfg['key'], $em, $am, $feature),
            'opencode-go' => new OpenCodeGoProvider($cfg['key'], $em, $am, $feature),
            'openrouter'  => new OpenRouterProvider($cfg['key'], $em, $am, $feature),
            'xai'         => new XaiProvider($cfg['key'], $em, $am, $feature),
            default       => throw new InvalidArgumentException("Unknown AI provider: {$providerName}"),
        };
    }

    /** @deprecated Use makeWithRuntimeFallback() for new code */
    public static function estimateCost(string $providerName, array $usage): float
    {
        $pricing = config("ai.providers.{$providerName}.pricing", []);

        return (($usage['input_tokens'] ?? 0) / 1_000_000) * ($pricing['input'] ?? 0)
             + (($usage['output_tokens'] ?? 0) / 1_000_000) * ($pricing['output'] ?? 0)
             + (($usage['cache_read_tokens'] ?? 0) / 1_000_000) * ($pricing['cache_read'] ?? 0)
             + (($usage['cache_creation_tokens'] ?? 0) / 1_000_000) * ($pricing['cache_write'] ?? 0);
    }

    /** Provider display metadata for admin panel */
    public static function providersStatus(): array
    {
        $providers = config('ai.providers', []);
        $status    = [];

        foreach ($providers as $key => $cfg) {
            $status[$key] = [
                'id'        => $key,
                'label'     => $cfg['label'] ?? $key,
                'has_key'   => !empty($cfg['key']),
                'model_extraction' => $cfg['models']['extraction'] ?? null,
                'model_advisor'    => $cfg['models']['advisor'] ?? null,
                'pricing'   => $cfg['pricing'] ?? [],
            ];
        }

        return $status;
    }
}
