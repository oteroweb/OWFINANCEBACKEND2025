<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\ProviderController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'providers',
], function () {
  //Provider ROUTES 
    Route::post('/', [ProviderController::class, 'save']);
    Route::get('/{id}', [ProviderController::class, 'find']);
    Route::put('/{id}', [ProviderController::class, 'update']);
    Route::get('/', [ProviderController::class, 'all']);
    Route::patch('/{id}/status', [ProviderController::class, 'change_status']);
    Route::get('/active', [ProviderController::class, 'allActive']);
    Route::delete('/{id}', [ProviderController::class, 'delete']);
    Route::get('/all', [ProviderController::class, 'withTrashed']);
  });