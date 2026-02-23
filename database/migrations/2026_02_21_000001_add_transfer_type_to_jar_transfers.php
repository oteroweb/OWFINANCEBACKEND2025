<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jar_transfers', function (Blueprint $table) {
            $table->enum('transfer_type', ['manual', 'leverage_auto'])
                ->default('manual')
                ->after('amount')
                ->comment('manual = user-initiated, leverage_auto = system leverage');
        });

        // Migrate existing leverage transfers based on description
        DB::table('jar_transfers')
            ->where('description', 'Apalancamiento automático')
            ->update(['transfer_type' => 'leverage_auto']);
    }

    public function down(): void
    {
        Schema::table('jar_transfers', function (Blueprint $table) {
            $table->dropColumn('transfer_type');
        });
    }
};
