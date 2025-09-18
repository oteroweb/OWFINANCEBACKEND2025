<?php

namespace Database\Factories;

use App\Models\Entities\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        // Ensure user_id refers to an existing user to satisfy FK constraints in tests
        $userId = \App\Models\User::query()->inRandomOrder()->value('id') ?? \App\Models\User::factory()->create()->id;
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'address' => $this->faker->address(),
            'user_id' => $userId,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
        ];
    }
}
