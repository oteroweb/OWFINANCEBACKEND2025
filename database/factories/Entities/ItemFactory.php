<?php

namespace Database\Factories\Entities;

use App\Models\Entities\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'last_price' => $this->faker->randomFloat(2, 1, 1000),
            'tax_id' => \App\Models\Entities\Tax::factory(),
            'active' => 1,
            'date' => $this->faker->date(),
            'custom_name' => $this->faker->word,
            'item_category_id' => \App\Models\Entities\ItemCategory::factory(),
        ];
    }
}
