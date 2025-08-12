<?php

namespace Database\Factories;

use App\Models\Entities\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'address' => $this->faker->address(),
            'user_id' => $this->faker->numberBetween(1, 10),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
        ];
    }
}
