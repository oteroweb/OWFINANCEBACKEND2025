<?php

namespace Database\Factories;

use App\Models\Entities\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'active' => 1,
        ];
    }
}
