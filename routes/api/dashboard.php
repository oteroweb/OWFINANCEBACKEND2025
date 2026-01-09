<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix' => 'dashboard',
], function () {
    Route::get('/user', [DashboardController::class, 'userSummary']);
    Route::get('/admin', [DashboardController::class, 'adminSummary'])
        ->middleware('App\\Http\\Middleware\\CheckRole:admin');
});
