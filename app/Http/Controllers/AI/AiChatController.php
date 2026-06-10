<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Entities\AiConversation;
use App\Models\Entities\AiConversationMessage;
use App\Models\Entities\AiUsageLog;
use App\Services\AI\AiProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    /**
     * POST /api/v1/ai/chat
     * Body: { message: string, conversation_id?: int }
     */
    public function chat(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message'         => 'required|string|max:2000',
            'conversation_id' => 'nullable|integer|exists:ai_conversations,id',
        ]);

        $user = $request->user();

        // Get or create conversation
        $conversation = ($validated['conversation_id'] ?? null)
            ? AiConversation::where('id', $validated['conversation_id'])
                             ->where('user_id', $user->id)
                             ->firstOrFail()
            : AiConversation::create([
                'user_id' => $user->id,
                'status'  => 'active',
            ]);

        // Load conversation history (last 20 messages for context window)
        $history = AiConversationMessage::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        // Get user context from cache
        $userContext = Cache::get("ai_user_context_{$user->id}", []);

        // Build system prompt with user context
        $systemPrompt = $this->buildAdvisorSystemPrompt($user, $userContext);

        // Build messages array
        $messages = $history->map(fn($msg) => [
            'role'    => $msg->role,
            'content' => $msg->content,
        ])->toArray();
        $messages[] = ['role' => 'user', 'content' => $validated['message']];

        // Save user message first
        AiConversationMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $validated['message'],
        ]);

        $startMs = now()->valueOf();

        return response()->stream(function () use ($conversation, $user, $systemPrompt, $messages, $startMs) {
            // Set SSE headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');

            $fullResponse = '';
            $usage        = [];
            $providerName = 'anthropic';
            $modelUsed    = '';

            try {
                $provider     = AiProviderFactory::make('advisor');
                $providerName = $provider->name();
                $modelUsed    = $provider->model();

                $result = $provider->streamChat(
                    $systemPrompt,
                    $messages,
                    function (string $text) use (&$fullResponse) {
                        $fullResponse .= $text;
                        echo "data: " . json_encode(['type' => 'delta', 'text' => $text]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                );

                $usage = $result['usage'];

            } catch (\Throwable $e) {
                Log::error('AI chat streaming error', ['error' => $e->getMessage()]);
                echo "data: " . json_encode(['type' => 'error', 'message' => 'Servicio no disponible']) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            $processingMs = now()->valueOf() - $startMs;

            // Save assistant message
            AiConversationMessage::create([
                'conversation_id'       => $conversation->id,
                'role'                  => 'assistant',
                'content'               => $fullResponse,
                'input_tokens'          => $usage['input_tokens'] ?? 0,
                'output_tokens'         => $usage['output_tokens'] ?? 0,
                'cache_read_tokens'     => $usage['cache_read_tokens'] ?? 0,
                'cache_creation_tokens' => $usage['cache_creation_tokens'] ?? 0,
                'processing_ms'         => $processingMs,
            ]);

            // Update conversation stats
            $conversation->increment('total_messages', 2);
            $conversation->update(['last_message_at' => now()]);

            // Log usage
            AiUsageLog::create([
                'user_id'               => $conversation->user_id,
                'feature'               => 'advisor',
                'provider_name'         => $providerName,
                'model_used'            => $modelUsed,
                'input_tokens'          => $usage['input_tokens'] ?? 0,
                'output_tokens'         => $usage['output_tokens'] ?? 0,
                'cache_read_tokens'     => $usage['cache_read_tokens'] ?? 0,
                'cache_creation_tokens' => $usage['cache_creation_tokens'] ?? 0,
                'estimated_cost_usd'    => AiProviderFactory::estimateCost($providerName, $usage),
                'date'                  => now()->toDateString(),
            ]);

            // Send done event with conversation_id for frontend
            echo "data: " . json_encode([
                'type'            => 'done',
                'conversation_id' => $conversation->id,
                'processing_ms'   => $processingMs,
            ]) . "\n\n";
            ob_flush();
            flush();

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * GET /api/v1/ai/conversations
     * List user's conversations
     */
    public function index(Request $request)
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->limit(20)
            ->get(['id', 'title', 'status', 'total_messages', 'last_message_at', 'created_at']);

        return response()->json($conversations);
    }

    /**
     * GET /api/v1/ai/conversations/{id}/messages
     */
    public function messages(Request $request, int $id)
    {
        $conversation = AiConversation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $messages = AiConversationMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $messages,
        ]);
    }

    private function buildAdvisorSystemPrompt($user, array $context): string
    {
        $name  = $context['user']['name'] ?? $user->name;
        $today = now()->toDateString();
        $month = now()->format('F Y');

        $totalBalance  = number_format($context['total_balance'] ?? 0, 2);
        $monthExpenses = number_format($context['current_month_summary']['total_expenses'] ?? 0, 2);
        $monthIncomes  = number_format($context['current_month_summary']['total_incomes'] ?? 0, 2);
        $monthNet      = number_format($context['current_month_summary']['net'] ?? 0, 2);

        $topCats = collect($context['top_categories_this_month'] ?? [])->map(
            fn($c) => "- {$c['category_name']}: \${$c['total']}"
        )->join("\n") ?: "Sin datos";

        return <<<SYSTEM
Eres el Asesor IA personal de {$name} en OwFinance, una app de finanzas personales.
Tu nombre es Asesor IA. Eres empático, claro, y usas términos financieros en español.

CONTEXTO FINANCIERO (actualizado hoy {$today}):
- Balance total cuentas: \${$totalBalance}
- {$month}: Gastos \${$monthExpenses} | Ingresos \${$monthIncomes} | Neto \${$monthNet}
- Top categorías de gasto este mes:
{$topCats}

INSTRUCCIONES:
- Responde siempre en español, de forma concisa y útil
- Basa tus análisis en los datos financieros reales del usuario
- Para registrar transacciones, indica al usuario que use los botones de la app
- No reveles datos sensibles de otros usuarios (no los tienes)
- Si el usuario pregunta por proyecciones, basa en datos históricos disponibles
- Máximo 3 párrafos por respuesta, a menos que el usuario pida más detalle
SYSTEM;
    }
}
