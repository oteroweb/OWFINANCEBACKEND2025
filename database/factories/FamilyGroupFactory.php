<?php

namespace Database\Factories;

use App\Models\Entities\FamilyGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyGroupFactory extends Factory
{
    protected $model = FamilyGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->lastName() . ' Family',
            'owner_user_id' => User::factory(),
        ];
    }
}
