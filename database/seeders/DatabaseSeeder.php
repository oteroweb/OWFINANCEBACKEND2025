<?php

namespace Database\Seeders;

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
            UserSeeder::class,
            AccountTypeSeeder::class,
            CurrencySeeder::class,
            ProviderSeeder::class,
            AccountSeeder::class,
            RateSeeder::class,
            TaxSeeder::class,
            CategorySeeder::class,
            JarSeeder::class,
            TransactionSeeder::class,
            ItemCategorySeeder::class,
            ItemTransactionSeeder::class,
            ItemSeeder::class,
            PaymentTransactionSeeder::class,
            PaymentTransactionTaxSeeder::class,
            AccountTaxSeeder::class,
        ]);
    }
}
