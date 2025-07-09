<?php
    
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\Route;

  Route::group([
    'middleware' => [
        'api',
    ],
    'prefix'     => 'api/1.0/account_types',
], function () {
  //Account Type ROUTES 
    Route::get('/all', ['uses'=> 'AccountTypeController@all']);
    Route::get('/all_active', ['uses'=> 'AccountTypeController@allActive']);
    Route::get('/{id}', 'AccountTypeController@find');
    Route::post('/save', 'AccountTypeController@save');
    Route::put('/update/{id}', 'AccountTypeController@update');
    Route::put('/change/active/{id}', 'AccountTypeController@change_status');
    Route::delete('/delete/{id}', 'AccountTypeController@delete');
  });