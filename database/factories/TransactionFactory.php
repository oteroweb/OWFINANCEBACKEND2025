<?php

namespace Database\Factories;

use App\Models\Entities\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {

        //   $table->id();
        //     $table->string('name', 100);
        //     $table->decimal('amount', 10, 2);
        //     $table->string('description')->nullable();
        //     $table->dateTime('date');
        //     $table->boolean('active')->default(true);
        //     $table->unsignedBigInteger('provider_id')->nullable();
        //     $table->string('url_file')->nullable();
        //     $table->unsignedBigInteger('rate_id')->nullable();
        //     $table->decimal('amount_tax', 10, 2)->nullable();
        //     $table->timestamps();
        //     $table->softDeletes();

        //     $table->foreign('provider_id')->references('id')->on('providers');
        $account = \App\Models\Entities\Account::factory()->create();
        $provider = \App\Models\Entities\Provider::factory()->create();
        $rate = \App\Models\Entities\Rate::factory()->create();
        $user = \App\Models\User::factory()->create();
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
            'transaction_type' => $this->faker->randomElement(['income','expense']),
            'user_id' => $user->id,
            'amount_tax' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
