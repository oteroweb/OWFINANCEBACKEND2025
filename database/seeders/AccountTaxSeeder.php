<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\AccountTax;
use Database\Factories\Entities\AccountTaxFactory;

class AccountTaxSeeder extends Seeder
{
    public function run(): void
    {
        AccountTax::factory()->count(10)->create();
    }
}
