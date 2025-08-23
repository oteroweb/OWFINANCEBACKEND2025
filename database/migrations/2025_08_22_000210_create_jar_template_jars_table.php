<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_template_jars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jar_template_id');
            $table->string('name', 100);
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('percent', 10, 2)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->enum('base_scope', ['all_income', 'categories'])->default('all_income');
            $table->string('color', 16)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('jar_template_id')->references('id')->on('jar_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_template_jars');
    }
};
