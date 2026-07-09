<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationsController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'notifications',
], function () {
    Route::get('/', [NotificationsController::class, 'index']);
});
