<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Entities\Jar;
use App\Policies\JarPolicy;
use App\Models\Entities\Category;
use App\Policies\CategoryPolicy;
use App\Models\Entities\Transaction;
use App\Policies\TransactionPolicy;
use App\Models\Entities\ItemTransaction;
use App\Policies\ItemTransactionPolicy;
use App\Models\Entities\Provider;
use App\Policies\ProviderPolicy;
use App\Models\Entities\AccountFolder;
use App\Policies\AccountFolderPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Jar::class => JarPolicy::class,
        Category::class => CategoryPolicy::class,
        Transaction::class => TransactionPolicy::class,
        ItemTransaction::class => ItemTransactionPolicy::class,
        Provider::class => ProviderPolicy::class,
        AccountFolder::class => AccountFolderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // #todo: Add gates/policies for Accounts, AccountFolder when ready.
    }
}
