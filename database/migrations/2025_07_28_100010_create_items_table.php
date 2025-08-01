<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('last_price', 10, 2)->nullable();
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->date('date')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->string('custom_name')->nullable();
            $table->unsignedBigInteger('item_category_id')->nullable();
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('set null');
            $table->foreign('item_category_id')->references('id')->on('item_categories')->onDelete('set null');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
