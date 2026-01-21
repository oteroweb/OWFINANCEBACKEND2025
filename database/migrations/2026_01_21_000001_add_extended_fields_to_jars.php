<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            $table->boolean('allow_negative_balance')->default(false)->after('refresh_mode');
            $table->decimal('negative_limit', 12, 2)->nullable()->after('allow_negative_balance');
            $table->date('start_date')->nullable()->after('negative_limit');
            $table->boolean('use_global_start_date')->default(true)->after('start_date');
            $table->enum('reset_cycle', ['none', 'monthly', 'quarterly', 'semiannual', 'annual'])
                ->default('none')
                ->after('use_global_start_date');
            $table->tinyInteger('reset_cycle_day')->default(1)->after('reset_cycle');
            $table->decimal('target_amount', 12, 2)->nullable()->after('reset_cycle_day');
            $table->date('last_reset_date')->nullable()->after('target_amount');
        });
    }

    public function down(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            $table->dropColumn([
                'allow_negative_balance',
                'negative_limit',
                'start_date',
                'use_global_start_date',
                'reset_cycle',
                'reset_cycle_day',
                'target_amount',
                'last_reset_date',
            ]);
        });
    }
};
