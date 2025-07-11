<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\ProviderController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'providers',
], function () {
  //Provider ROUTES 
    Route::get('/', [ProviderController::class, 'all']);
    Route::get('/all', [ProviderController::class, 'withTrashed']);
    Route::get('/active', [ProviderController::class, 'allActive']);
    Route::get('/{id}', [ProviderController::class, 'find']);
    Route::post('/', [ProviderController::class, 'save']);
    Route::put('/{id}', [ProviderController::class, 'update']);
    Route::patch('/{id}/status', [ProviderController::class, 'change_status']);
    Route::delete('/{id}', [ProviderController::class, 'delete']);
  });