<?php

namespace Database\Factories;

use App\Models\Entities\ItemTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Entities\Transaction;
use App\Models\Entities\Tax;
use App\Models\Entities\Rate;

class ItemTransactionFactory extends Factory
{
    protected $model = ItemTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'name'           => $this->faker->word(),
            'amount'         => $this->faker->randomFloat(2, 1, 1000),
            'tax_id'         => Tax::factory(),
            'rate_id'        => Rate::factory(),
            'description'    => $this->faker->sentence(),
            'jar_id'         => null,
            'active'         => $this->faker->boolean(),
            'date'           => $this->faker->dateTimeThisYear(),
            'category_id'    => null,
            'user_id'        => null,
            'custom_name'    => $this->faker->word(),
        ];
    }
}
