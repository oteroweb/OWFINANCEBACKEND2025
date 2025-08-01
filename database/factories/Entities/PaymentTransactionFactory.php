<?php

namespace Database\Factories\Entities;

use App\Models\Entities\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_id' => 1, // Ajusta según tus tests
            'account_id' => 1, // Ajusta según tus tests
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'active' => 1,
        ];
    }
}
