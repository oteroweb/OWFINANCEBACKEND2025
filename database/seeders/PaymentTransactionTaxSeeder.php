<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\PaymentTransactionTax;
use Database\Factories\Entities\PaymentTransactionTaxFactory;

class PaymentTransactionTaxSeeder extends Seeder
{
    public function run(): void
    {
        PaymentTransactionTax::factory()->count(10)->create();
    }
}
