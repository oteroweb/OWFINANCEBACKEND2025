<?php

namespace Database\Factories;

use App\Models\Entities\ItemTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemTaxFactory extends Factory
{
    protected $model = ItemTax::class;

    public function definition(): array
    {
        return [
            'item_transaction_id' => 1, // Ajusta según tus tests
            'tax_id' => 1, // Ajusta según tus tests
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'active' => 1,
            'date' => $this->faker->date(),
        ];
    }
}
