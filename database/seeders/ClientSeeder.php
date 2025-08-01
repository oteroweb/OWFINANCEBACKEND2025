<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::factory()->count(10)->create();
    }
}
