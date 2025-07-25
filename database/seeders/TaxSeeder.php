<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Tax;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        Tax::updateOrCreate(
            ['name' => 'IGTF'],
            ['percent' => 3.00, 'active' => true]
        );

        Tax::updateOrCreate(
            ['name' => 'Contribuyente Especial'],
            ['percent' => 0, 'active' => true]
        );

        Tax::updateOrCreate(
            ['name' => 'IVA'],
            ['percent' => 16.00, 'active' => true]
        );

        Tax::factory()->count(10)->create();
    }
}
