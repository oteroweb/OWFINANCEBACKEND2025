<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\TransactionType;

class TransactionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Income', 'slug' => 'income'],
            ['name' => 'Expense', 'slug' => 'expense'],
            ['name' => 'Transfer', 'slug' => 'transfer'],
            ['name' => 'Payment', 'slug' => 'payment'],
        ];
        foreach ($defaults as $data) {
            TransactionType::firstOrCreate(['slug' => $data['slug']], $data + ['active'=>1]);
        }
    }
}
