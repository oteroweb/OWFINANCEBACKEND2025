<?php

use Illuminate\Support\Facades\Route;

// Versioned health probe for monitoring / load balancers.
// Returns the standard API envelope so clients can parse it uniformly.
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'code' => 200,
        'message' => 'healthy',
        'data' => [
            'service' => 'owfinance-backend',
            'env' => app()->environment(),
        ],
    ]);
});
