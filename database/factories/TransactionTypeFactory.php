<?php

namespace Database\Factories;

use App\Models\Entities\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionTypeFactory extends Factory
{
    protected $model = TransactionType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['income','expense','transfer','payment']);
        return [
            'name' => ucfirst($name),
            'slug' => $name,
            'description' => $this->faker->sentence(),
            'active' => 1,
        ];
    }
}
