<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\AccountTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Entities\AccountType;
use Tests\TestCase;

/**
 * OWF-377: AccountTypeSeeder::run() usaba AccountType::create() sin guard —
 * cada re-ejecución manual duplicaba los 7 tipos globales. En prod llegó a
 * correr 11 veces (77 filas, todas con el mismo set de 7 nombres), dejando
 * el selector de "Tipo de cuenta" inusable para cualquier usuario nuevo.
 */
class AccountTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_seeder_twice_does_not_duplicate_global_types()
    {
        (new AccountTypeSeeder())->run();
        $this->assertEquals(7, AccountType::whereNull('user_id')->count());

        (new AccountTypeSeeder())->run();
        (new AccountTypeSeeder())->run();
        $this->assertEquals(7, AccountType::whereNull('user_id')->count());

        $names = AccountType::whereNull('user_id')->pluck('name')->sort()->values()->all();
        $this->assertEquals(
            ['Cashea', 'Con interes', 'Cuenta Bancaria', 'Deuda', 'Efectivo', 'Prestamo', 'Tarjeta de Credito'],
            $names
        );
    }
}
