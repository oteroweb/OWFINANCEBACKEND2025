<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\PaymentTransactionTax;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\Tax;

class PaymentTransactionTaxSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base taxes exist
    $igtf = Tax::firstOrCreate(['name' => 'IGTF'], ['percent' => 3.00, 'applies_to' => 'payment', 'active' => true]);
    $pm = Tax::firstOrCreate(['name' => 'Comisión Pago Móvil'], ['percent' => 0.30, 'applies_to' => 'payment', 'active' => true]);

        // For each payment transaction, optionally add IGTF (e.g., for foreign currency payments)
        $payments = PaymentTransaction::all();
        foreach ($payments as $pt) {
            // IGTF 3% on payment amount
            PaymentTransactionTax::updateOrCreate(
                [
                    'payment_transaction_id' => $pt->id,
                    'tax_id' => $igtf->id,
                ],
                [
                    'percent' => $igtf->percent,
                    'amount' => round(($pt->amount ?? 0) * ($igtf->percent / 100), 2),
                    'active' => 1,
                ]
            );

            // Comisión Pago Móvil 0.3% (only if desired; here we seed for all for demo)
            PaymentTransactionTax::updateOrCreate(
                [
                    'payment_transaction_id' => $pt->id,
                    'tax_id' => $pm->id,
                ],
                [
                    'percent' => $pm->percent,
                    'amount' => round(($pt->amount ?? 0) * ($pm->percent / 100), 2),
                    'active' => 1,
                ]
            );
        }
    }
}
