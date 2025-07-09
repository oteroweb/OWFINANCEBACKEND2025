<?php
    
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Route;

  Route::group([
    'middleware' => [
        'api',
    ],
    'prefix'     => 'api/1.0/providers',
], function () {
  //Provider ROUTES 
    Route::get('/all', ['uses'=> 'ProviderController@all']);
    Route::get('/all_active', ['uses'=> 'ProviderController@allActive']);
    Route::get('/{id}', 'ProviderController@find');
    Route::post('/save', 'ProviderController@save');
    Route::put('/update/{id}', 'ProviderController@update');
    Route::put('/change/active/{id}', 'ProviderController@change_status');
    Route::delete('/delete/{id}', 'ProviderController@delete');
  });