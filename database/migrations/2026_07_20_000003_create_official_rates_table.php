<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('rate', 12, 4);
            $table->string('source')->default('pydolarve');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['currency_id', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_rates');
    }
};
