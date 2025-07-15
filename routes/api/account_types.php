<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\AccountTypeController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'account_types',
], function () {
  //Account Type ROUTES 
    Route::post('/', [AccountTypeController::class, 'save']);
    Route::get('/{id}', [AccountTypeController::class, 'find']);
    Route::put('/{id}', [AccountTypeController::class, 'update']);
    Route::get('/', [AccountTypeController::class, 'all']);
    Route::patch('/{id}/status', [AccountTypeController::class, 'change_status']);
    Route::get('/active', [AccountTypeController::class, 'allActive']);
    Route::delete('/{id}', [AccountTypeController::class, 'delete']);
    Route::get('/all', [AccountTypeController::class, 'withTrashed']);
  });