<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\ItemTransaction;

class ItemTransactionSeeder extends Seeder
{
    public function run(): void
    {
        ItemTransaction::factory()->count(20)->create();
    }
}
