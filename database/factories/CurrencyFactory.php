<?php

namespace Database\Factories;

use App\Models\Entities\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->currencyCode(),
            'name' => $this->faker->word(),
            'align' => $this->faker->randomElement(['left', 'right']),
            // 'rounding' => $this->faker->randomFloat(2, 0, 100),
            // 'name_plural' => $this->faker->word() . 's',
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥', '₹', '₩', '₽', '₪', '₫', '₱']),
        ];
    }
}
