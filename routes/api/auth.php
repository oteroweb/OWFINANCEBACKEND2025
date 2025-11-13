<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::group([
    'prefix' => 'auth',
], function () {
    Route::post('/login', [AuthController::class, 'login']);
    // Logout requires auth
    Route::middleware(['auth:sanctum'])->post('/logout', [AuthController::class, 'logout']);
});
