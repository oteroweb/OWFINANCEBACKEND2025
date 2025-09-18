<?php

namespace Database\Factories;

use App\Models\Entities\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        // Use existing records only; do not create related models here
        $accountId = \App\Models\Entities\Account::query()->inRandomOrder()->value('id')
            ?? \App\Models\Entities\Account::factory()->create()->id;
        $providerId = \App\Models\Entities\Provider::query()->inRandomOrder()->value('id')
            ?? \App\Models\Entities\Provider::factory()->create()->id;
        $rateId = \App\Models\Entities\Rate::query()->inRandomOrder()->value('id')
            ?? \App\Models\Entities\Rate::factory()->create()->id;
        $userId = \App\Models\User::query()->inRandomOrder()->value('id')
            ?? \App\Models\User::factory()->create()->id;
        $typeId = \App\Models\Entities\TransactionType::query()->inRandomOrder()->value('id')
            ?? \App\Models\Entities\TransactionType::factory()->create()->id;
        return [
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'description' => $this->faker->sentence(),
            'account_id' => $accountId,
            'name' => $this->faker->word(),
            'date' => $this->faker->dateTimeThisYear(),
            'active' => $this->faker->randomElement([1, 0]),
            'provider_id' => $providerId,
            'url_file' => $this->faker->url(),
            'rate_id' => $rateId,
            'transaction_type_id' => $typeId,
            'user_id' => $userId,
            'amount_tax' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
