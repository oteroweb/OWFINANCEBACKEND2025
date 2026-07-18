<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Entities\AiExtraction;
use App\Models\Entities\AiUsageLog;
use App\Models\Entities\Provider;
use App\Models\Entities\UserCurrency;
use App\Services\AI\AiProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiExtractionController extends Controller
{
    public function extract(Request $request)
    {
        $validated = $request->validate([
            'source'     => 'required|in:voice,ocr,auto',
            // OWF-311: 'input' ahora es opcional cuando source=voice trae 'audio' —
            // se transcribe en el servidor y el resultado pasa a ser el 'input' real.
            'input'      => 'nullable|string|max:5000',
            'image'      => 'nullable|string',
            'audio'      => 'nullable|string',
            'audio_mime' => 'nullable|string|max:50',
        ]);

        if (empty($validated['input']) && empty($validated['audio']) && empty($validated['image'])) {
            return response()->json(['error' => 'Falta input, audio o image.'], 422);
        }

        $user    = $request->user();
        $startMs = now()->valueOf();

        $transcribedFrom = null;
        if ($validated['source'] === 'voice' && !empty($validated['audio']) && empty($validated['input'])) {
            try {
                $validated['input'] = AiProviderFactory::transcribeAudio(
                    $validated['audio'],
                    $validated['audio_mime'] ?? 'audio/webm'
                );
                $transcribedFrom = 'groq-whisper';
            } catch (\Throwable $e) {
                Log::error('Audio transcription failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'No se pudo transcribir el audio. Intenta de nuevo.'], 503);
            }
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userMessage  = $this->buildUserMessage($validated);

        try {
            $provider = AiProviderFactory::makeWithRuntimeFallback('extraction');
            $result   = $provider->extract($systemPrompt, $userMessage);
        } catch (\Throwable $e) {
            Log::error('AI extraction failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'AI service unavailable'], 503);
        }

        $content = $result['content'];
        $usage   = $result['usage'];
        // OWF-309: el chain (`$provider`, ver AiProviderFactory::makeWithRuntimeFallback) no es
        // el proveedor que realmente respondió — es el wrapper de la cadena completa. Su
        // name()/model() siempre devuelven el string de TODOS los proveedores configurados y el
        // modelo del primero, sin importar cuál contestó. `$result['provider']`/`['model']`
        // (agregados en AiProviderChain::extract()) sí identifican al proveedor real ganador.
        $actualProvider = $result['provider'] ?? $provider->name();
        $actualModel    = $result['model'] ?? $provider->model();
        // OWF-308: el chain ya valida que el content sea JSON parseable antes de aceptar la
        // respuesta de un proveedor (ver AiProviderChain::parseJsonContent) — se reusa el mismo
        // parser acá (en vez del `json_decode($content, true) ?? []` anterior) para no volver a
        // introducir el mismo fallo silencioso si algún día `extract()` deja de pasar por el chain.
        try {
            $extracted = \App\Services\AI\AiProviderChain::parseJsonContent($content);
        } catch (\Throwable $e) {
            Log::error('AI extraction returned invalid JSON after all providers', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'AI service unavailable'], 503);
        }
        // OWF-316: el nombre de comercio que la IA transcribe puede traer errores de
        // transcripción/OCR (ej. "Vanesco" en vez de "Banesco") — se resuelve contra los
        // providers reales del usuario (propios + globales) por similitud, no por igualdad
        // exacta, para no perder el match por un typo de un solo caracter.
        $this->resolveProviderSuggestion($extracted, $user->id);

        // OWF-317: la extracción no tiene noción de tasas de cambio por sí sola — se calcula
        // el equivalente en bolívares a la tasa oficial (BCV) del usuario de forma determinística
        // en PHP (no se le pide a la IA que haga la matemática, es propenso a error), reusando
        // las tasas que el usuario ya configuró en /user/config (user_currencies).
        $this->attachBcvEquivalent($extracted, $user->id);

        $processingMs = now()->valueOf() - $startMs;

        $extraction = AiExtraction::create([
            'user_id'           => $user->id,
            'source'            => $validated['source'],
            'raw_input'         => $validated['input'],
            'extracted_data'    => $extracted,
            'confidence_score'  => $extracted['confidence'] ?? null,
            'model_used'        => $actualModel,
            'input_tokens'      => $usage['input_tokens'],
            'output_tokens'     => $usage['output_tokens'],
            'cache_read_tokens' => $usage['cache_read_tokens'],
            'processing_ms'     => $processingMs,
        ]);

        $this->logUsage($user->id, $validated['source'], $usage, $actualProvider, $actualModel);

        return response()->json([
            'extraction_id' => $extraction->id,
            'data'          => $extracted,
            'processing_ms' => $processingMs,
            // OWF-311: cuando el input vino de audio transcrito en el servidor, el
            // frontend ya no tiene el texto de antemano (antes lo tenía vía
            // SpeechRecognition del navegador) — se lo devolvemos para mostrar "escuché: …".
            'transcript'     => $transcribedFrom ? $validated['input'] : null,
        ]);
    }

    private function buildSystemPrompt(): string
    {
        $today = now()->toDateString();

        return <<<PROMPT
Eres un asistente de finanzas personales. Tu tarea es extraer datos de una transacción financiera a partir del texto del usuario.

Responde ÚNICAMENTE con un JSON válido con esta estructura:
{
  "type": "expense|income|transfer",
  "amount": 0.00,
  "currency": "USD",
  "description": "descripción corta",
  "category_suggestion": "categoría sugerida",
  "merchant": "nombre del comercio o entidad mencionada, o null",
  "date": "YYYY-MM-DD",
  "confidence": 0.95
}

Reglas:
- type: expense si es un gasto, income si es un ingreso, transfer si es una transferencia
- amount: número positivo siempre
- currency: la moneda EN LA QUE EL USUARIO EXPRESÓ el monto tal cual (si dice "45 dólares", currency=USD y amount=45 — no conviertas la cifra a otra moneda, eso lo hace el sistema después con las tasas reales del usuario)
- merchant: nombre propio del comercio/entidad si se menciona (ej. "Banesco", "Farmatodo"), sin el resto de la frase — null si no se menciona ninguno
- date: hoy si no se especifica (hoy es {$today})
- confidence: qué tan seguro estás de la extracción (0.0 a 1.0)
- Si no puedes extraer un campo, usa null
PROMPT;
    }

    private function buildUserMessage(array $validated): array
    {
        if ($validated['source'] === 'ocr' && !empty($validated['image'])) {
            return [
                [
                    'type'   => 'image',
                    'source' => [
                        'type'       => 'base64',
                        'media_type' => 'image/jpeg',
                        'data'       => $validated['image'],
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => 'Extrae la información de esta imagen de ticket o factura.',
                ],
            ];
        }

        return [['type' => 'text', 'text' => $validated['input']]];
    }

    /**
     * OWF-316: resuelve el "merchant" que devolvió la IA contra los providers reales del
     * usuario (propios + globales, igual scope que ProviderRepo::allActive) por similitud de
     * texto — la transcripción/OCR puede traer typos de un caracter (Vanesco → Banesco) que un
     * match exacto o "LIKE %texto%" no captura. Adjunta provider_id_suggestion +
     * provider_name_suggestion a $extracted (por referencia) cuando encuentra un match razonable.
     */
    private function resolveProviderSuggestion(array &$extracted, int $userId): void
    {
        $merchant = trim((string) ($extracted['merchant'] ?? ''));
        if ($merchant === '') {
            return;
        }

        $candidates = Provider::where('active', 1)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->get(['id', 'name']);

        $needle = mb_strtolower($merchant);
        $best = null;
        $bestRatio = 0.0;

        foreach ($candidates as $candidate) {
            $hay = mb_strtolower((string) $candidate->name);
            $maxLen = max(mb_strlen($needle), mb_strlen($hay));
            if ($maxLen === 0) {
                continue;
            }
            $distance = levenshtein($needle, $hay);
            $ratio = 1 - ($distance / $maxLen);
            if ($ratio > $bestRatio) {
                $bestRatio = $ratio;
                $best = $candidate;
            }
        }

        // Umbral 0.6: tolera 1-2 caracteres de diferencia en nombres cortos (Vanesco↔Banesco
        // = 0.857) sin matchear pares de providers genuinamente distintos.
        if ($best && $bestRatio >= 0.6) {
            $extracted['provider_id_suggestion'] = $best->id;
            $extracted['provider_name_suggestion'] = $best->name;
        }
    }

    /**
     * OWF-317: la IA no debe hacer matemática de conversión de moneda (poco confiable) — acá se
     * calcula el equivalente en la moneda local del usuario a la tasa oficial (BCV) que el propio
     * usuario ya configuró en /user/config, de forma determinística. Solo aplica cuando el monto
     * extraído está en USD y el usuario tiene una tasa oficial configurada para otra moneda
     * (típicamente VES) — ese es el caso real reportado ("45 dólares" sin equivalente en bolívares).
     */
    private function attachBcvEquivalent(array &$extracted, int $userId): void
    {
        $amount = $extracted['amount'] ?? null;
        $currency = strtoupper((string) ($extracted['currency'] ?? ''));
        if (!is_numeric($amount) || $currency !== 'USD') {
            return;
        }

        $officialRate = UserCurrency::with('currency')
            ->where('user_id', $userId)
            ->where('is_official', true)
            ->whereHas('currency', function ($q) {
                $q->where('code', '!=', 'USD');
            })
            ->orderByDesc('updated_at')
            ->first();

        if (!$officialRate || !$officialRate->current_rate) {
            return;
        }

        $extracted['bcv_equivalent']    = round(((float) $amount) * (float) $officialRate->current_rate, 2);
        $extracted['bcv_currency_code'] = $officialRate->currency->code ?? null;
        $extracted['bcv_rate']          = (float) $officialRate->current_rate;
    }

    private function logUsage(int $userId, string $source, array $usage, string $providerName, string $modelUsed): void
    {
        $featureMap = ['voice' => 'voice', 'ocr' => 'ocr', 'auto' => 'auto_ia'];

        AiUsageLog::create([
            'user_id'               => $userId,
            'feature'               => $featureMap[$source] ?? 'auto_ia',
            'provider_name'         => substr($providerName, 0, 100),
            'model_used'            => substr($modelUsed, 0, 100),
            'input_tokens'          => $usage['input_tokens'],
            'output_tokens'         => $usage['output_tokens'],
            'cache_read_tokens'     => $usage['cache_read_tokens'],
            'cache_creation_tokens' => $usage['cache_creation_tokens'],
            'estimated_cost_usd'    => AiProviderFactory::estimateCost($providerName, $usage),
            'date'                  => now()->toDateString(),
        ]);
    }
}
