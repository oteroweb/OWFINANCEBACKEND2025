<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add "Ajuste de saldo" to transaction_types
        if (!DB::table('transaction_types')->where('slug', 'ajuste')->exists()) {
            DB::table('transaction_types')->insert([
                'name' => 'Ajuste de saldo',
                'slug' => 'ajuste',
                'description' => 'Ajuste manual de saldo de cuenta',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    public function down(): void
    {
    DB::table('transaction_types')->where('slug', 'ajuste')->delete();
    }
};
