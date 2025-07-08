<?php
    
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Route;

  Route::group([
    'middleware' => [
        'api',
    ],
    'prefix'     => 'api/1.0/currencies',
], function () {
  //Currency ROUTES 
    Route::get('/all', ['uses'=> 'CurrencyController@all']);
    Route::get('/all_active', ['uses'=> 'CurrencyController@allActive']);
    Route::get('/{id}', 'CurrencyController@find');
    Route::post('/save', 'CurrencyController@save');
    Route::put('/update/{id}', 'CurrencyController@update');
    Route::put('/change/active/{id}', 'CurrencyController@change_status');
    Route::delete('/delete/{id}', 'CurrencyController@delete');
  });