<?php

namespace Database\Factories;

use App\Models\Entities\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $accountTypeId = \App\Models\Entities\AccountType::query()->inRandomOrder()->value('id')
            ?? \App\Models\Entities\AccountType::factory()->create()->id;
        $currencyId = \App\Models\Entities\Currency::query()->inRandomOrder()->value('id')
            ?? \App\Models\Entities\Currency::factory()->create()->id;
        return [
            'name' => $this->faker->word(),
            'balance' => $this->faker->randomFloat(2, 100, 10000),
            'account_type_id' => $accountTypeId,
            'active' => $this->faker->randomElement([1, 0]),
            'initial' => $this->faker->randomFloat(2, 0, 1000),
            'currency_id' => $currencyId,
        ];
    }
}
