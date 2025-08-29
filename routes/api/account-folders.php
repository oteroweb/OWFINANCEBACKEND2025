<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountFolderController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'account-folders',
], function () {
    // Account Folder ROUTES
    Route::post('/', [AccountFolderController::class, 'store']);
});
