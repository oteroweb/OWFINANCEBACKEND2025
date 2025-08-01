<?php

namespace Database\Factories;

use App\Models\Entities\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        return [
            'name' => $this->faker->word,
            'last_price' => $this->faker->randomFloat(2, 1, 1000),
            'tax_id' => $tax->id,
            'active' => 1,
            'date' => $this->faker->date(),
            'custom_name' => $this->faker->word,
            'item_category_id' => $itemCategory->id,
        ];
    }
}
