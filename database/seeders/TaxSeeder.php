<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Tax;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        // Venezuela common taxes
        Tax::updateOrCreate(
            ['name' => 'IVA'],
            ['percent' => 16.00, 'applies_to' => 'item', 'active' => true]
        );

        Tax::updateOrCreate(
            ['name' => 'IGTF'],
            ['percent' => 3.00, 'applies_to' => 'payment', 'active' => true]
        );

        // Exento (0%) for items/products without VAT
        Tax::updateOrCreate(
            ['name' => 'Exento'],
            ['percent' => 0.00, 'applies_to' => 'item', 'active' => true]
        );

        // Pago Móvil commission (0.3%) often charged by banks/payment rails
        Tax::updateOrCreate(
            ['name' => 'Comisión Pago Móvil'],
            ['percent' => 0.30, 'applies_to' => 'payment', 'active' => true]
        );

        // Optional: keep any legacy entries used by earlier data
        Tax::updateOrCreate(
            ['name' => 'Contribuyente Especial'],
            ['percent' => 0.00, 'applies_to' => 'item', 'active' => true]
        );

        // You can still create additional random taxes for tests if needed
        // Tax::factory()->count(10)->create();
    }
}
