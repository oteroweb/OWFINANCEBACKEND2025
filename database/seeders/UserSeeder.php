<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

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

        User::updateOrCreate([
            'email' => 'admin@demo.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);
        User::updateOrCreate([
            'email' => 'user@demo.com',
        ], [
            'name' => 'Usuario',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);
        User::updateOrCreate([
            'email' => 'guest@demo.com',
        ], [
            'name' => 'Invitado',
            'password' => Hash::make('password'),
            'role_id' => $guestRole->id,
        ]);
        User::updateOrCreate([
            'email' => 'otero@demo.com',
        ], [
            'name' => 'Jose Otero',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);
        // Create additional users
        User::factory()->count(10)->create()->each(function ($user) use ($userRole) {
            $user->role()->associate($userRole);
            $user->save();
        });
    }
}
