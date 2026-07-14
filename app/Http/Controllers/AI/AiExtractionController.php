<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Entities\AiExtraction;
use App\Models\Entities\AiUsageLog;
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
  "date": "YYYY-MM-DD",
  "confidence": 0.95
}

Reglas:
- type: expense si es un gasto, income si es un ingreso, transfer si es una transferencia
- amount: número positivo siempre
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
