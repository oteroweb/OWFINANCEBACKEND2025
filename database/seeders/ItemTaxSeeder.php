<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\ItemTax;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\Tax;

class ItemTaxSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base taxes exist
    $iva = Tax::firstOrCreate(['name' => 'IVA'], ['percent' => 16.00, 'applies_to' => 'item', 'active' => true]);
    $exento = Tax::firstOrCreate(['name' => 'Exento'], ['percent' => 0.00, 'applies_to' => 'item', 'active' => true]);

        // Assign taxes to each item transaction based on whether it already has a tax_id
        $items = ItemTransaction::all();
        foreach ($items as $it) {
            // If item transaction already references a tax_id, honor it and create ItemTax record accordingly
            $baseTax = null;
            if (!empty($it->tax_id)) {
                $baseTax = Tax::find($it->tax_id) ?: $iva;
            } else {
                // Default some items to IVA and others to Exento to have coverage
                $baseTax = ($it->id % 3 === 0) ? $exento : $iva;
            }
            ItemTax::updateOrCreate(
                [
                    'item_transaction_id' => $it->id,
                    'tax_id' => $baseTax->id,
                ],
                [
                    'percent' => $baseTax->percent,
                    'amount' => round(($it->amount ?? 0) * ($baseTax->percent / 100), 2),
                    'active' => 1,
                    'date' => now()->toDateString(),
                ]
            );
        }
    }
}
