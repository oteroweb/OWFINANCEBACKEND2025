<?php

namespace Database\Factories;

use App\Models\Entities\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_id' => null, // Debe ser seteado explícitamente si se requiere integridad
            'account_id' => null, // Debe ser seteado explícitamente si se requiere integridad
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'active' => 1,
        ];
    }

    public function forTransaction($transaction)
    {
        return $this->state(function (array $attributes) use ($transaction) {
            return [
                'transaction_id' => $transaction->id,
                'account_id' => $transaction->account_id,
            ];
        });
    }
}
