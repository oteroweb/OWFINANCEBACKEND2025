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
            'date' => $this->faker->date(),
            'active' => $this->faker->boolean(90),
        ];
    }
}
