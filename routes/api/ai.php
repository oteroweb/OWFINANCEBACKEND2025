<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AI\AiChatController;
use App\Http\Controllers\AI\AiExtractionController;
use App\Http\Controllers\AI\AiUserContextController;

Route::middleware(['auth:sanctum', 'throttle:ai'])->prefix('ai')->group(function () {
    Route::post('extract-transaction', [AiExtractionController::class, 'extract'])
         ->middleware(['throttle:ai-user', 'ai.budget']);

    // OWF-319 (capa 1): resuelve un campo faltante (ej. account_id) sin llamar a la IA —
    // por eso NO lleva 'ai.budget', esta llamada no consume presupuesto de IA.
    Route::post('extract-transaction/{extraction}/answer', [AiExtractionController::class, 'answer'])
         ->middleware('throttle:ai-user');

    Route::get('user-context', [AiUserContextController::class, 'context'])
         ->middleware('throttle:ai-user');

    Route::post('chat', [AiChatController::class, 'chat'])
         ->middleware(['throttle:ai-advisor', 'ai.budget']);

    Route::get('conversations', [AiChatController::class, 'index'])
         ->middleware('throttle:ai-user');

    Route::get('conversations/{id}/messages', [AiChatController::class, 'messages'])
         ->middleware('throttle:ai-user');
});
