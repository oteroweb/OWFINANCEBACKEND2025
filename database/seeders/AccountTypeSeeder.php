<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\AccountType;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        // AccountType::factory()->count(5)->create();
        $types = [
            [
                'name' => 'Cuenta Bancaria',
                'description' => 'Cuenta bancaria tradicional para ingresos y gastos.',
                'icon' => 'bank',
                'active' => 1,
            ],
            [
                'name' => 'Tarjeta de Credito',
                'description' => 'Tarjeta de crédito para compras y pagos diferidos.',
                'icon' => 'credit-card',
                'active' => 1,
            ],
            [
                'name' => 'Con interes',
                'description' => 'Cuenta con intereses generados sobre el saldo.',
                'icon' => 'percent',
                'active' => 1,
            ],
            [
                'name' => 'Deuda',
                'description' => 'Registro de deudas o cuentas por pagar.',
                'icon' => 'debt',
                'active' => 1,
            ],
            [
                'name' => 'Prestamo',
                'description' => 'Préstamos otorgados o recibidos.',
                'icon' => 'loan',
                'active' => 1,
            ],
            [
                'name' => 'Efectivo',
                'description' => 'Dinero en efectivo disponible.',
                'icon' => 'cash',
                'active' => 1,
            ],
            [
                'name' => 'Cashea',
                'description' => 'Cuenta para operaciones de Cashéa.',
                'icon' => 'wallet',
                'active' => 1,
            ],
        ];

        // OWF-377: `create()` sin guard corría de nuevo cada vez que alguien ejecutaba
        // este seeder a mano (backfill de datos base en un ambiente nuevo, etc.) —
        // en prod terminó corriendo 11 veces, duplicando los 7 tipos globales a 77
        // filas y volviendo el selector de "Tipo de cuenta" inusable. firstOrCreate
        // por nombre (scope global, user_id null) lo hace seguro de re-ejecutar.
        foreach ($types as $data) {
            AccountType::firstOrCreate(
                ['name' => $data['name'], 'user_id' => null],
                $data
            );
        }
    }
}
