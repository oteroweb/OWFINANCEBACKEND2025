<?php

namespace Database\Factories\Entities;

use App\Models\Entities\ItemTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemTaxFactory extends Factory
{
    protected $model = ItemTax::class;

    public function definition(): array
    {
        return [
            'item_transaction_id' => \App\Models\Entities\ItemTransaction::factory(),
            'tax_id' => \App\Models\Entities\Tax::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'active' => 1,
            'date' => $this->faker->date(),
        ];
    }
}
