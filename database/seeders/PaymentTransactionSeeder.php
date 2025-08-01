<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\PaymentTransaction;
use Database\Factories\Entities\PaymentTransactionFactory;

class PaymentTransactionSeeder extends Seeder
{
    public function run(): void
    {
        PaymentTransaction::factory()->count(10)->create();
    }
}
