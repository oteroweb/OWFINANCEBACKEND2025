<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Transaction;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        Transaction::factory()->count(20)->create();
    }
}
