<?php

namespace Database\Seeders;

use App\Models\Entities\Client;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AccountTypeSeeder::class,
            CurrencySeeder::class,
            // ClientSeeder::class,
            UserSeeder::class,
            ProviderSeeder::class,
            RateSeeder::class,
            AccountSeeder::class,
            TransactionTypeSeeder::class,
            TransactionSeeder::class,
            TaxSeeder::class,
            // CategorySeeder::class,
            JarSeeder::class,
            JarTemplateSeeder::class,
            CategoryTemplateSeeder::class,
            ItemCategorySeeder::class,
            ItemTransactionSeeder::class,
            ItemSeeder::class,
            PaymentTransactionSeeder::class,
            PaymentTransactionTaxSeeder::class,
            AccountTaxSeeder::class,
        ]);
    }
}
