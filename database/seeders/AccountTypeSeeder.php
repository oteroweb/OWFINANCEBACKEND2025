<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\AccountType;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        AccountType::factory()->count(5)->create();
    }
}
