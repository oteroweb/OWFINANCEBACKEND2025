<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jar_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('jar_id')->references('id')->on('jars')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->unique(['jar_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_category');
    }
};
