<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Services\CategoryTreeInitializer;
use App\Models\Entities\Transaction;
use App\Observers\TransactionObserver;

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

    }
}
