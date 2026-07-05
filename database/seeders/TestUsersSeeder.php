<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Entities\Currency;
use App\Models\Entities\UserSetting;
use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use Illuminate\Support\Facades\Hash;

/**
 * Siembra dos usuarios de prueba para Playwright E2E:
 *
 *   usertestpro@demo.com  — plan Pro, 2 cuentas (USD + VES), activa multi-moneda y Pro features
 *   usertestlite@demo.com — plan Lite, 1 cuenta (USD), valida comportamiento base
 *
 * Idempotente: puede correr múltiples veces sin duplicar registros.
 * Contraseña de ambos: S$ratoga.1990
 */
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::where('slug', 'user')->firstOrFail();

        $usd = Currency::where('code', 'USD')->first();
        $ves = Currency::where('code', 'VES')->first();

        $accountType = AccountType::where('name', 'like', '%efectivo%')
            ->orWhere('name', 'like', '%cash%')
            ->orWhere('name', 'like', '%billetera%')
            ->first()
            ?? AccountType::first();

        // ── usertestpro@demo.com ──────────────────────────────────────────────
        $pro = User::updateOrCreate(
            ['email' => 'usertestpro@demo.com'],
            [
                'name'        => 'Test Pro',
                'password'    => Hash::make('S$ratoga.1990'),
                'role_id'     => $userRole->id,
                'currency_id' => $usd?->id ?? Currency::first()->id,
            ]
        );

        UserSetting::updateOrCreate(
            ['user_id' => $pro->id],
            [
                'layout_mode'         => 'pro',
                'has_seen_onboarding' => true,
            ]
        );

        // Cuentas para usuario pro — relación many-to-many via account_user
        $accUsd = Account::firstOrCreate(
            ['name' => 'Billetera USD [testpro]'],
            [
                'currency_id'     => $usd?->id ?? Currency::first()->id,
                'account_type_id' => $accountType?->id,
                'balance'         => 1000.00,
                'initial'         => 1000.00,
            ]
        );
        $pro->accounts()->syncWithoutDetaching([$accUsd->id]);

        if ($ves) {
            $accVes = Account::firstOrCreate(
                ['name' => 'Billetera VES [testpro]'],
                [
                    'currency_id'     => $ves->id,
                    'account_type_id' => $accountType?->id,
                    'balance'         => 50000.00,
                    'initial'         => 50000.00,
                ]
            );
            $pro->accounts()->syncWithoutDetaching([$accVes->id]);
        }

        // ── usertestlite@demo.com ─────────────────────────────────────────────
        $lite = User::updateOrCreate(
            ['email' => 'usertestlite@demo.com'],
            [
                'name'        => 'Test Lite',
                'password'    => Hash::make('S$ratoga.1990'),
                'role_id'     => $userRole->id,
                'currency_id' => $usd?->id ?? Currency::first()->id,
            ]
        );

        UserSetting::updateOrCreate(
            ['user_id' => $lite->id],
            [
                'layout_mode'         => 'lite',
                'has_seen_onboarding' => true,
            ]
        );

        // Una sola cuenta USD
        $accLite = Account::firstOrCreate(
            ['name' => 'Billetera [testlite]'],
            [
                'currency_id'     => $usd?->id ?? Currency::first()->id,
                'account_type_id' => $accountType?->id,
                'balance'         => 500.00,
                'initial'         => 500.00,
            ]
        );
        $lite->accounts()->syncWithoutDetaching([$accLite->id]);

        $this->command->info('TestUsersSeeder: usertestpro@demo.com + usertestlite@demo.com OK');
    }
}
