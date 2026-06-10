<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AI\AiChatController;
use App\Http\Controllers\AI\AiExtractionController;
use App\Http\Controllers\AI\AiUserContextController;

Route::middleware(['auth:sanctum', 'throttle:ai'])->prefix('ai')->group(function () {
    Route::post('extract-transaction', [AiExtractionController::class, 'extract'])
         ->middleware(['throttle:ai-user', 'ai.budget']);

    Route::get('user-context', [AiUserContextController::class, 'context'])
         ->middleware('throttle:ai-user');

    Route::post('chat', [AiChatController::class, 'chat'])
         ->middleware(['throttle:ai-advisor', 'ai.budget']);

    Route::get('conversations', [AiChatController::class, 'index'])
         ->middleware('throttle:ai-user');

    Route::get('conversations/{id}/messages', [AiChatController::class, 'messages'])
         ->middleware('throttle:ai-user');
});
