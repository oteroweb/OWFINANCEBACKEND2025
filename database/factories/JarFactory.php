<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Entities\Jar;

class JarFactory extends Factory
{
    protected $model = Jar::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->word(),
            'is_active'  => $this->faker->boolean(90),
            'percent'    => $this->faker->randomFloat(2, 0, 100),
            'type'       => $this->faker->word(),
            'active'     => $this->faker->boolean(90),
            'date'       => $this->faker->date(),
        ];
    }
}
