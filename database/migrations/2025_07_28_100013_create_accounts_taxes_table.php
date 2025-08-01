<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tax_id');
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('percent', 10, 2)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('accounts_taxes');
    }
};
