<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            $table->foreignId('leverage_from_jar_id')
                ->nullable()
                ->constrained('jars')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leverage_from_jar_id');
        });
    }
};
