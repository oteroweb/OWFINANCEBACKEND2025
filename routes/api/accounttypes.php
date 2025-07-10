<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\AccountTypeController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'account_types',
], function () {
  //Account Type ROUTES 
    Route::get('/', [AccountTypeController::class, 'all']);
    Route::get('/active', [AccountTypeController::class, 'allActive']);
    Route::get('/{id}', [AccountTypeController::class, 'find']);
    Route::post('/', [AccountTypeController::class, 'save']);
    Route::put('/{id}', [AccountTypeController::class, 'update']);
    Route::patch('/{id}/status', [AccountTypeController::class, 'change_status']);
    Route::delete('/{id}', [AccountTypeController::class, 'delete']);
  });