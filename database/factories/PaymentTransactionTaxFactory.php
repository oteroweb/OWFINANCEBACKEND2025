<?php

namespace Database\Factories;

use App\Models\Entities\PaymentTransactionTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionTaxFactory extends Factory
{
    protected $model = PaymentTransactionTax::class;

    public function definition(): array
    {
        $paymentTransaction = \App\Models\Entities\PaymentTransaction::factory()->create();
        // Use an existing Tax if available; fallback to create one if none exist
        $taxId = \App\Models\Entities\Tax::inRandomOrder()->value('id')
            ?? \App\Models\Entities\Tax::factory()->create()->id;
        return [
            'payment_transaction_id' => $paymentTransaction->id,
            'tax_id' => $taxId,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'active' => 1,
        ];
    }
}
