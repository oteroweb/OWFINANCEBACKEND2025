<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JarTemplateController;

Route::group([
    'middleware' => ['api','auth:sanctum'],
    'prefix'     => 'jar-templates',
], function () {
    Route::get('/', [JarTemplateController::class, 'index']);
    Route::post('/apply', [JarTemplateController::class, 'apply']);
});
