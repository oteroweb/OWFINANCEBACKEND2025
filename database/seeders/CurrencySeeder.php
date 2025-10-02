<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Currency::factory()->count(5)->create();
        $currencies = [
            // Principales
            [ 'name' => 'Dólar Estadounidense', 'symbol' => '$',  'align' => 'left', 'code' => 'USD', 'active' => 1 ],
            [ 'name' => 'Euro',                 'symbol' => '€',  'align' => 'left', 'code' => 'EUR', 'active' => 1 ],
            [ 'name' => 'Peso Chileno',         'symbol' => '$',  'align' => 'left', 'code' => 'CLP', 'active' => 1 ],
            [ 'name' => 'Peso Mexicano',        'symbol' => 'MX$', 'align' => 'left', 'code' => 'MXN', 'active' => 1 ],
            [ 'name' => 'Bolívar Venezolano',   'symbol' => 'Bs.', 'align' => 'left', 'code' => 'VES', 'active' => 1 ],
            [ 'name' => 'Libra Esterlina',      'symbol' => '£',  'align' => 'left', 'code' => 'GBP', 'active' => 1 ],
            // Otras populares
            [ 'name' => 'Yen Japonés',          'symbol' => '¥',  'align' => 'left', 'code' => 'JPY', 'active' => 1 ],
            [ 'name' => 'Yuan Chino',           'symbol' => '¥',  'align' => 'left', 'code' => 'CNY', 'active' => 1 ],
            [ 'name' => 'Real Brasileño',       'symbol' => 'R$', 'align' => 'left', 'code' => 'BRL', 'active' => 1 ],
            [ 'name' => 'Dólar Canadiense',     'symbol' => 'C$', 'align' => 'left', 'code' => 'CAD', 'active' => 1 ],
                // Criptomonedas
                [ 'name' => 'Tether',               'symbol' => '$T',  'align' => 'left', 'code' => 'USDT', 'active' => 1 ],
                [ 'name' => 'USD Coin',             'symbol' => '$C',  'align' => 'left', 'code' => 'USDC', 'active' => 1 ],
                [ 'name' => 'Bitcoin',              'symbol' => '₿',  'align' => 'left', 'code' => 'BTC', 'active' => 1 ],
                [ 'name' => 'Ethereum',             'symbol' => 'Ξ',  'align' => 'left', 'code' => 'ETH', 'active' => 1 ],
                [ 'name' => 'BNB',                  'symbol' => 'BNB','align' => 'left', 'code' => 'BNB', 'active' => 1 ],
        ];

        // Idempotent seeding: upsert by unique code
        foreach ($currencies as $data) {
            Currency::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'align' => $data['align'],
                    'active' => $data['active'],
                ]
            );
        }
    }
}
