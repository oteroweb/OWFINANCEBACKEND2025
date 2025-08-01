<?php

namespace Database\Factories;

use App\Models\Entities\PaymentTransactionTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionTaxFactory extends Factory
{
    protected $model = PaymentTransactionTax::class;

    public function definition(): array
    {
        return [
            'payment_transaction_id' => 1, // Ajusta según tus tests
            'tax_id' => 1, // Ajusta según tus tests
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'active' => 1,
        ];
    }
}
