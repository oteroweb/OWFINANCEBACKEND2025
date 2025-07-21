<?php

namespace Database\Factories;

use App\Models\Entities\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountTypeFactory extends Factory
{
    protected $model = AccountType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->randomElement([
                'fa-solid fa-user',
                'fa-solid fa-bank',
                'fa-solid fa-credit-card',
                'fa-solid fa-wallet',
                'fa-solid fa-piggy-bank',
                'fa-solid fa-money-bill',
                'fa-solid fa-coins',
                'fa-solid fa-chart-line',
                'fa-solid fa-briefcase',
                'fa-solid fa-cash-register',
            ]),
        ];
    }
}
