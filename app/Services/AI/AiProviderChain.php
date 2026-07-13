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
                // OWF-308: valida que el contenido sea JSON parseable ANTES de aceptar la
                // respuesta — algunos modelos (ej. deepseek-v4-flash vía opencode-go) envuelven
                // la respuesta en fences markdown (```json ... ```) o agregan prosa alrededor
                // pese al system prompt pidiendo "solo JSON". Antes esto no se detectaba acá:
                // el chain devolvía el content tal cual con HTTP 200, y el controller hacía
                // `json_decode($content, true) ?? []` silenciosamente, sin lanzar excepción —
                // así que el fallback a groq/openrouter/gemini NUNCA se activaba, y el usuario
                // recibía un 200 con `data` completamente vacío (monto/moneda/descripción null).
                self::parseJsonContent($result['content'] ?? '');
                // OWF-309: taggear el proveedor Y modelo REALES que respondieron — antes el
                // controller usaba `$chain->name()`/`$chain->model()` (siempre el string de la
                // cadena completa "opencode-go+groq+..." y el modelo del PRIMER proveedor
                // configurado, sin importar cuál realmente contestó), haciendo que los logs de
                // AiExtraction/AiUsageLog fueran engañosos apenas ocurriera un fallback real.
                $result['provider'] = $provider->name();
                $result['model']    = $provider->model();
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

    /**
     * Extrae y parsea el JSON de una respuesta de modelo, tolerando fences markdown
     * (```json ... ``` o ``` ... ```) que algunos proveedores agregan pese a pedir "solo JSON".
     * Lanza si el contenido no es JSON válido, para que el llamador pueda reintentar con el
     * siguiente proveedor de la cadena.
     */
    public static function parseJsonContent(string $content): array
    {
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed);
            $trimmed = preg_replace('/\s*```\s*$/', '', $trimmed);
            $trimmed = trim($trimmed);
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('AI response was not valid JSON: ' . substr($trimmed, 0, 200));
        }

        return $decoded;
    }

    public function streamChat(string $systemPrompt, array $messages, callable $onDelta): array
    {
        // Try streaming with each provider; if the first fails before producing output, try next
        $lastError = null;

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->streamChat($systemPrompt, $messages, $onDelta);
                // OWF-309: mismo fix que extract() — taggear el proveedor/modelo REALES que
                // respondieron, no el string de la cadena completa.
                $result['provider'] = $provider->name();
                $result['model']    = $provider->model();
                return $result;
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
