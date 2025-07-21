<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Rate;

class RateSeeder extends Seeder
{
    public function run(): void
    {
        Rate::factory()->count(10)->create();
    }
}
