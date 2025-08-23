<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('jar_template_jar_categories');
    Schema::enableForeignKeyConstraints();

    Schema::create('jar_template_jar_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jar_template_jar_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('jar_template_jar_id', 'fk_jtj_cat_tpljar')->references('id')->on('jar_template_jars')->onDelete('cascade');
            $table->foreign('category_id', 'fk_jtj_cat_cat')->references('id')->on('categories')->onDelete('cascade');
            $table->unique(['jar_template_jar_id', 'category_id'], 'uniq_jtj_cat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_template_jar_categories');
    }
};
