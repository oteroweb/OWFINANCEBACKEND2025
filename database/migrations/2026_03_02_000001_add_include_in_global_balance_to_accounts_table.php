<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'include_in_global_balance')) {
                $table->boolean('include_in_global_balance')
                    ->default(true)
                    ->after('active')
                    ->comment('Si la cuenta se incluye en el balance global del usuario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'include_in_global_balance')) {
                $table->dropColumn('include_in_global_balance');
            }
        });
    }
};
