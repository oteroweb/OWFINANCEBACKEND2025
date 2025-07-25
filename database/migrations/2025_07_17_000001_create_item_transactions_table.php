<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('name', 100);
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('jar_id')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->dateTime('date');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('custom_name')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('rate_id')->references('id')->on('rates')->onDelete('set null');
            $table->foreign('jar_id')->references('id')->on('jar')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_transactions');
    }
};
