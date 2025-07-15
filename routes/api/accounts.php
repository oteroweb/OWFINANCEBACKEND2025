<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'v1/accounts',
], function () {
    //Account ROUTES 
    Route::post('/', [AccountController::class, 'save']);
    Route::get('/{id}', [AccountController::class, 'find']);
    Route::put('/{id}', [AccountController::class, 'update']);
    Route::get('/', [AccountController::class, 'all']);
    Route::patch('/{id}/status', [AccountController::class, 'change_status']);
    Route::get('/active', [AccountController::class, 'allActive']);
    Route::delete('/{id}', [AccountController::class, 'delete']);
    Route::get('/all', [AccountController::class, 'withTrashed']);
});
