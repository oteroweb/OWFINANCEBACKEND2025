<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\Entities\Currency;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $userRole = Role::where('slug', 'user')->first();
        $guestRole = Role::where('slug', 'guest')->first();

        $currencyIds = Currency::pluck('id')->all();
        $randomCurrency = function() use ($currencyIds) {
            return $currencyIds[array_rand($currencyIds)];
        };

        User::updateOrCreate([
            'email' => 'admin@demo.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'currency_id' => $randomCurrency(),
        ]);
        User::updateOrCreate([
            'email' => 'user@demo.com',
        ], [
            'name' => 'Usuario',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'currency_id' => $randomCurrency(),
        ]);
        User::updateOrCreate([
            'email' => 'guest@demo.com',
        ], [
            'name' => 'Invitado',
            'password' => Hash::make('password'),
            'role_id' => $guestRole->id,
            'currency_id' => $randomCurrency(),
        ]);
        User::updateOrCreate([
            'email' => 'otero@demo.com',
        ], [
            'name' => 'Jose Otero',
            'password' => Hash::make('password'),
            // 'client_id' => 1, // Assuming a client with ID 1 exists
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'role_id' => $userRole->id,
        ]);
        // Create additional users
        User::factory()->count(10)->create()->each(function ($user) use ($userRole, $randomCurrency) {
            $user->role()->associate($userRole);
            $user->currency_id = $randomCurrency();
            $user->save();
        });
    }
}
