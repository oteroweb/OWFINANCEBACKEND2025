<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemCategoryController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'item-categories',
], function () {
    Route::post('/', [ItemCategoryController::class, 'save']);
    Route::get('/active', [ItemCategoryController::class, 'allActive']);
    Route::get('/all', [ItemCategoryController::class, 'withTrashed']);
    Route::get('/', [ItemCategoryController::class, 'all']);
    Route::patch('/{id}/status', [ItemCategoryController::class, 'change_status']);
    Route::put('/{id}', [ItemCategoryController::class, 'update']);
    Route::delete('/{id}', [ItemCategoryController::class, 'delete']);
    Route::get('/{id}', [ItemCategoryController::class, 'find']);
});
