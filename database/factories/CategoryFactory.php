<?php

namespace Database\Factories;

use App\Models\Entities\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name'  => $this->faker->word(),
            'active'=> $this->faker->boolean(90),
            'date'  => $this->faker->date(),
            'parent_id' => null,
        ];
    }
}
