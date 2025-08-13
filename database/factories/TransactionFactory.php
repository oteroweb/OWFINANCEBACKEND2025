<?php

namespace Database\Factories;

use App\Models\Entities\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {

        $account = \App\Models\Entities\Account::factory()->create();
        $provider = \App\Models\Entities\Provider::factory()->create();
        $rate = \App\Models\Entities\Rate::factory()->create();
        $user = \App\Models\User::factory()->create();

        // ensure some transaction types exist
        $typeSlug = $this->faker->randomElement(['income','expense','transfer','payment']);
        $type = \App\Models\Entities\TransactionType::firstOrCreate(
            ['slug' => $typeSlug],
            ['name' => ucfirst($typeSlug), 'active' => 1]
        );
        return [
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'description' => $this->faker->sentence(),
            'account_id' => $account->id,
            'name' => $this->faker->word(),
            'date' => $this->faker->dateTimeThisYear(),
            'active' => $this->faker->randomElement([1, 0]),
            'provider_id' => $provider->id,
            'url_file' => $this->faker->url(),
            'rate_id' => $rate->id,
            'transaction_type_id' => $type->id,
            'user_id' => $user->id,
            'amount_tax' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
