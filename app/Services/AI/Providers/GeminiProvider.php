<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProviderInterface;

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

        // OWF-131: gemini-2.5-flash es un modelo "thinking" — gasta una porción de
        // maxOutputTokens en razonamiento interno (usageMetadata.thoughtsTokenCount) ANTES
        // de escribir la respuesta visible. Con maxOutputTokens=512 el presupuesto se
        // agotaba en el razonamiento y la respuesta JSON quedaba cortada a la mitad (ej.
        // `{"type":"expense",...,"category_suggestion":"` sin cerrar) — parseJsonContent()
        // la rechazaba como JSON inválido y el chain caía en silencio al siguiente
        // proveedor, sin dejar rastro claro del motivo real. `thinkingBudget: 0` desactiva
        // el razonamiento (no hace falta para extraer datos estructurados de un texto/imagen
        // corta) y libera todo el presupuesto para la respuesta real.
        //
        // OWF-131: causa raíz REAL encontrada (no era la key, no era Guzzle específicamente):
        // para cuerpos POST de más de ~1KB, curl agrega automáticamente el header
        // `Expect: 100-continue`. Con la key real del usuario (formato `AQ.`, la que Google
        // emite ahora para su cuenta) + un payload de este tamaño (prompt con lista de
        // cuentas + imagen en base64, siempre >1KB), Google responde 401
        // "ACCESS_TOKEN_TYPE_UNSUPPORTED" — un mensaje totalmente engañoso para lo que en
        // realidad es una negociación de `Expect: 100-continue` que su API no maneja bien.
        // Confirmado aislando variable por variable con curl crudo (prompt corto: funciona;
        // prompt real completo + imagen: 401; el MISMO payload con el header `Expect:`
        // (vacío, desactiva la negociación) explícito: 200 limpio). `Illuminate\Http`
        // (Guzzle) tiene el mismo problema por el mismo motivo — no es exclusivo de curl,
        // cualquier cliente que no desactive `Expect: 100-continue` lo va a pisar con esta
        // combinación de key+tamaño de payload.
        $payload = json_encode([
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents'           => [['role' => 'user', 'parts' => $parts]],
            'generationConfig'   => [
                'maxOutputTokens'  => 1024,
                'responseMimeType' => 'application/json',
                'thinkingConfig'   => ['thinkingBudget' => 0],
            ],
        ]);

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            // 'Expect:' vacío desactiva el header automático 'Expect: 100-continue' de curl
            // para POSTs grandes — ver comentario arriba, esa era la causa real del 401.
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Expect:'],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body     = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlErr  = curl_errno($curlHandle) ? curl_error($curlHandle) : null;
        curl_close($curlHandle);

        if ($curlErr) {
            throw new \RuntimeException("Gemini transport error: {$curlErr}");
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Gemini API error: ' . $body);
        }

        $data    = json_decode($body, true) ?? [];
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

        $usage     = ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_creation_tokens' => 0];
        $rawOutput = '';

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            // OWF-131: mismo fix que extract() — 'Expect:' vacío evita el 401 engañoso que
            // Google devuelve ante 'Expect: 100-continue' en POSTs grandes (conversaciones
            // largas con esta key superan fácil el umbral de ~1KB que lo dispara).
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Expect:'],
            // OWF-131: mismo fix que extract() — thinkingBudget:0 evita que el modelo gaste
            // el presupuesto de tokens en razonamiento interno antes de la respuesta visible.
            CURLOPT_POSTFIELDS => json_encode([
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents'           => $contents,
                'generationConfig'   => [
                    'maxOutputTokens' => 2048,
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($onDelta, &$usage, &$rawOutput) {
                $rawOutput .= $data;
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
        // OWF-310: mismo fix que OpenCodeGoProvider — validar el HTTP status real en vez
        // de asumir éxito solo porque curl no tiró un error de transporte.
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlErr  = curl_errno($curlHandle) ? curl_error($curlHandle) : null;
        curl_close($curlHandle);

        if ($curlErr) {
            throw new \RuntimeException("Gemini streamChat transport error: {$curlErr}");
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Gemini streamChat HTTP {$httpCode}: " . substr($rawOutput, 0, 300));
        }

        return ['usage' => $usage, 'model' => $model];
    }

    public function name(): string { return 'gemini'; }

    public function model(): string
    {
        return $this->feature === 'advisor' ? $this->advisorModel : $this->extractionModel;
    }
}
