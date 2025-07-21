<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::factory()->count(5)->create();
    }
}
