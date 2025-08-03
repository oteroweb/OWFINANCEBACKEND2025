<?php

namespace Database\Factories;

use App\Models\Entities\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

class RateFactory extends Factory
{
    protected $model = Rate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'value' => $this->faker->randomFloat(2, 0, 1000), // Random float between 0 and 1000 with 2 decimal place
            'date' => $this->faker->date(),
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
