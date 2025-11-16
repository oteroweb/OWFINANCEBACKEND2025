<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            foreach (glob(base_path('routes/api/*.php')) as $routeFile) {
                Route::middleware('api')
                    ->prefix('api/v1')
                    ->group($routeFile);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unify API error responses for authentication/authorization
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('api/v1/*')) {
                return response()->json([
                    'status' => 'ERROR',
                    'code' => 401,
                    'message' => __('Unauthenticated.'),
                    'data' => [],
                ], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('api/v1/*')) {
                return response()->json([
                    'status' => 'ERROR',
                    'code' => 403,
                    'message' => $e->getMessage() ?: __('This action is unauthorized.'),
                    'data' => [],
                ], 403);
            }
        });
    })->create();
