<?php

namespace Database\Factories;

use App\Models\Entities\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'applies_to' => $this->faker->randomElement(['item','payment','both']),
            'active' => $this->faker->randomElement([1, 0]),
            'date' => $this->faker->date(),
        ];
    }
}
