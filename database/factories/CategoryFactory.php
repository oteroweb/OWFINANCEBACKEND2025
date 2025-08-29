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
            'icon' => $this->faker->optional()->randomElement(['wallet','shopping-cart','home','utensils','car','gift']),
            'transaction_type_id' => null,
            'include_in_balance' => true,
            'type' => 'category',
            'sort_order' => 0,
        ];
    }
}
