<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CurrencyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::controller(CurrencyController::class)->prefix('currencies')->group(function () {
//     Route::get('/all', 'all');
//     Route::get('/all-active', 'allActive');
//     Route::get('/find/{id}', 'find');
//     Route::post('/save', 'save');
//     Route::put('/update/{id}', 'update');
//     Route::delete('/delete/{id}', 'delete');
//     Route::patch('/change-status/{id}', 'change_status');
//     Route::get('/with-trashed', 'withTrashed');
// });
