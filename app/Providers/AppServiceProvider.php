<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Services\CategoryTreeInitializer;
use App\Models\Entities\Transaction;
use App\Observers\TransactionObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-seed default category tree for new users
        User::created(function (User $user) {
            try {
                app(CategoryTreeInitializer::class)->seedForUser($user->id);
            } catch (\Throwable $e) {
                // Swallow errors to not block user creation; consider logging
                logger()->error($e);
            }
        });

    // Observers
    Transaction::observe(TransactionObserver::class);
    User::observe(UserObserver::class);

        // Rate limiters for AI routes
        // Layer 1: IP throttle (60 req/min per IP)
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Layer 2: Per-user AI feature limits
        RateLimiter::for('ai-user', function (Request $request) {
            return [
                Limit::perMinute(20)->by('ai-user:' . ($request->user()?->id ?? $request->ip())),
                Limit::perDay(500)->by('ai-user-daily:' . ($request->user()?->id ?? $request->ip())),
            ];
        });

        // Layer 3: Per-user per-feature limits (heavier features)
        RateLimiter::for('ai-advisor', function (Request $request) {
            return [
                Limit::perMinute(10)->by('ai-advisor:' . ($request->user()?->id ?? $request->ip())),
                Limit::perDay(100)->by('ai-advisor-daily:' . ($request->user()?->id ?? $request->ip())),
            ];
        });

    }
}
