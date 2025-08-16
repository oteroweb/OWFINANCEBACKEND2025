<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\Currency;
use App\Models\User;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // Account::factory()->count(10)->create();

        $user = User::where('email', 'otero@owfinance.online')->first();
        if (!$user) {
            // If the user doesn't exist, skip seeding accounts to avoid orphan data
            return;
        }

        // Ensure currency IDs are available
        $currencyIds = Currency::whereIn('code', ['USD','EUR','VES'])->pluck('id', 'code');
        if ($currencyIds->count() < 3) {
            // Required currencies are missing
            return;
        }

        $items = [
            // name, amount, code
            ['Fernando', -400.00, 'USD'],
            ['Binance Funding Oteroxx', 0.00, 'USD'],
            ['JL banesco DOLARES', 83.00, 'USD'],
            ['Cuenta Ticktaps', -75.53, 'USD'],
            ['Cuentas', 24.34, 'USD'],
            ['JL Banesco', 381.15, 'VES'],
            ['JL Mercantil', 2643.43, 'VES'],
            ['Banca amiga', 129.16, 'VES'],
            ['paypal Joseluis Dolares', 0.00, 'USD'],
            ['deudas', 373.78, 'USD'],
            ['Deuda Hermano Dolares', 359.63, 'USD'],
            ['deuda pendient bolivares', -1124.81, 'VES'],
            ['Platzi deuda a mi hermano', -53.97, 'USD'],
            ['Deuda Karo', 103.99, 'USD'],
            ['cashea', 0.00, 'USD'],
            ['Dinero Padres', 0.00, 'USD'],
            ['deuda Pareja', -3523.95, 'VES'],
            ['Efectivo', 3609.43, 'USD'],
            ['Dolares efectivo', 3370.00, 'USD'],
            ['EFECTIVO EUROS', 185.00, 'EUR'],
            ['efectivo bolivares', 480.00, 'VES'],
            ['cuenta cartera dolares', 66.00, 'USD'],
            ['Ahorro', 3603.92, 'USD'],
            ['Binance Ahorro', -538.00, 'USD'],
            ['DAP HERMANO', 1413.00, 'USD'],
            ['payoneer', 2728.92, 'USD'],
            ['Sin nombre', 0.00, 'USD'],
            ['Paypal Euros Jose luis', 0.00, 'EUR'],
        ];

        foreach ($items as [$name, $amount, $code]) {
            $currencyId = $currencyIds[$code] ?? null;
            if (!$currencyId) continue;
            $typeId = AccountType::inRandomOrder()->value('id');
            if (!$typeId) continue;

            $account = Account::updateOrCreate(
                ['name' => $name],
                [
                    'currency_id' => $currencyId,
                    'initial' => $amount,
                    'balance' => $amount,
                    'account_type_id' => $typeId,
                    'active' => 1,
                ]
            );

            // Attach to user without detaching existing
            $user->accounts()->syncWithoutDetaching([$account->id]);
        }
    }
}
