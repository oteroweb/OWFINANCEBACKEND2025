<?php

namespace Database\Factories\Entities;

use App\Models\Entities\AccountTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountTaxFactory extends Factory
{
    protected $model = AccountTax::class;

    public function definition(): array
    {
        return [
            'account_id' => 1, // Ajusta según tus tests
            'tax_id' => 1, // Ajusta según tus tests
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'active' => 1,
        ];
    }
}
