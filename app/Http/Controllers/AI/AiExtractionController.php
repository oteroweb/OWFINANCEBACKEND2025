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
            'source' => 'required|in:voice,ocr,auto',
            'input'  => 'required|string|max:5000',
            'image'  => 'nullable|string',
        ]);

        $user    = $request->user();
        $startMs = now()->valueOf();

        $systemPrompt = $this->buildSystemPrompt();
        $userMessage  = $this->buildUserMessage($validated);

        try {
            $provider = AiProviderFactory::make('extraction');
            $result   = $provider->extract($systemPrompt, $userMessage);
        } catch (\Throwable $e) {
            Log::error('AI extraction failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'AI service unavailable'], 503);
        }

        $content      = $result['content'];
        $usage        = $result['usage'];
        $extracted    = json_decode($content, true) ?? [];
        $processingMs = now()->valueOf() - $startMs;

        $extraction = AiExtraction::create([
            'user_id'           => $user->id,
            'source'            => $validated['source'],
            'raw_input'         => $validated['input'],
            'extracted_data'    => $extracted,
            'confidence_score'  => $extracted['confidence'] ?? null,
            'model_used'        => $provider->model(),
            'input_tokens'      => $usage['input_tokens'],
            'output_tokens'     => $usage['output_tokens'],
            'cache_read_tokens' => $usage['cache_read_tokens'],
            'processing_ms'     => $processingMs,
        ]);

        $this->logUsage($user->id, $validated['source'], $usage, $provider->name(), $provider->model());

        return response()->json([
            'extraction_id' => $extraction->id,
            'data'          => $extracted,
            'processing_ms' => $processingMs,
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
            'provider_name'         => $providerName,
            'model_used'            => $modelUsed,
            'input_tokens'          => $usage['input_tokens'],
            'output_tokens'         => $usage['output_tokens'],
            'cache_read_tokens'     => $usage['cache_read_tokens'],
            'cache_creation_tokens' => $usage['cache_creation_tokens'],
            'estimated_cost_usd'    => AiProviderFactory::estimateCost($providerName, $usage),
            'date'                  => now()->toDateString(),
        ]);
    }
}
