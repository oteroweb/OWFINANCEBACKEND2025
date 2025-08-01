<?php

namespace Database\Factories;

use App\Models\Entities\ItemTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Entities\Transaction;
use App\Models\Entities\Tax;
use App\Models\Entities\Rate;
use App\Models\Entities\Jar;

class ItemTransactionFactory extends Factory
{
    protected $model = ItemTransaction::class;

    public function definition(): array
    {
        return [
            'item_id'        => \App\Models\Entities\Item::factory(),
            'transaction_id' => Transaction::factory(),
            'quantity'       => $this->faker->numberBetween(1, 10),
            'name'           => $this->faker->word(),
            'amount'         => $this->faker->randomFloat(2, 1, 1000),
            'tax_id'         => Tax::factory(),
            'rate_id'        => Rate::factory(),
            'description'    => $this->faker->sentence(),
            'jar_id'         => \App\Models\Entities\Jar::factory(),
            'active'         => $this->faker->boolean(),
            'date'           => $this->faker->dateTimeThisYear(),
            'category_id'    => null,
            'user_id'        => null,
            'custom_name'    => $this->faker->word(),
        ];
    }
}
