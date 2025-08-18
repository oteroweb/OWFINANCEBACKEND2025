<?php

namespace Database\Factories\Entities;

use App\Models\Entities\ItemTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemTaxFactory extends Factory
{
    protected $model = ItemTax::class;

    public function definition(): array
    {
        $itemTransactionId = \App\Models\Entities\ItemTransaction::inRandomOrder()->value('id')
            ?? \App\Models\Entities\ItemTransaction::factory()->create()->id;
        $taxId = \App\Models\Entities\Tax::inRandomOrder()->value('id')
            ?? \App\Models\Entities\Tax::factory()->create()->id;
        return [
            'item_transaction_id' => $itemTransactionId,
            'tax_id' => $taxId,
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'percent' => $this->faker->randomFloat(2, 0, 100),
            'active' => 1,
            'date' => $this->faker->date(),
        ];
    }
}
