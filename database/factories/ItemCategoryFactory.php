<?php

namespace Database\Factories;

use App\Models\Entities\ItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemCategoryFactory extends Factory
{
    protected $model = ItemCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'active' => 1,
            'date' => $this->faker->date(),
        ];
    }
}
