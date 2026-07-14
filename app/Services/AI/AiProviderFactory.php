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
        $vm = $cfg['models']['vision'] ?? null;

        return match ($providerName) {
            'anthropic'   => new AnthropicProvider($cfg['key'], $em, $am, $feature),
            'gemini'      => new GeminiProvider($cfg['key'], $em, $am, $feature),
            'openai'      => new OpenAiProvider($cfg['key'], $em, $am, $feature),
            'groq'        => new GroqProvider($cfg['key'], $em, $am, $feature, $vm),
            'opencode-go' => new OpenCodeGoProvider($cfg['key'], $em, $am, $feature, $vm),
            'openrouter'  => new OpenRouterProvider($cfg['key'], $em, $am, $feature, $vm),
            'xai'         => new XaiProvider($cfg['key'], $em, $am, $feature, $vm),
            default       => throw new InvalidArgumentException("Unknown AI provider: {$providerName}"),
        };
    }

    /**
     * OWF-311: transcribe audio a texto vía Groq Whisper. Se usa un método dedicado (no
     * el chain de chat/extracción) porque la transcripción es un tipo de llamada distinto
     * (multipart/audio, no chat completions) — Groq es el único proveedor con key
     * funcionando de forma confiable en este momento y su Whisper es rápido y barato.
     * Reemplaza la dependencia de `SpeechRecognition`/`webkitSpeechRecognition` del
     * navegador, que no es confiable cross-browser (Brave la bloquea, iOS Safari nunca la
     * implementó) — grabar el audio con MediaRecorder y transcribir en el servidor sí
     * funciona en cualquier navegador/plataforma, incluyendo el build de Capacitor iOS.
     */
    public static function transcribeAudio(string $audioBase64, string $mimeType = 'audio/webm'): string
    {
        $cfg = config('ai.providers.groq');
        if (!$cfg || empty($cfg['key'])) {
            throw new \RuntimeException('Transcripción de audio no disponible: Groq no está configurado.');
        }

        $model     = $cfg['models']['transcription'] ?? 'whisper-large-v3-turbo';
        $audioData = base64_decode($audioBase64, true);
        if ($audioData === false) {
            throw new \RuntimeException('Audio inválido: no se pudo decodificar base64.');
        }

        $extension = match (true) {
            str_contains($mimeType, 'webm') => 'webm',
            str_contains($mimeType, 'mp4')  => 'mp4',
            str_contains($mimeType, 'ogg')  => 'ogg',
            str_contains($mimeType, 'wav')  => 'wav',
            default                          => 'webm',
        };

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => "Bearer {$cfg['key']}",
        ])
            ->timeout(30)
            ->attach('file', $audioData, "audio.{$extension}")
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                'model'    => $model,
                'language' => 'es',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Groq transcription API error: ' . $response->body());
        }

        $text = trim((string) ($response->json('text') ?? ''));
        if ($text === '') {
            throw new \RuntimeException('La transcripción resultó vacía — intenta hablar más claro o más cerca del micrófono.');
        }

        return $text;
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
