<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('parent_slug')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('jar_tpljar_cat_tpl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jar_template_jar_id');
            $table->unsignedBigInteger('category_template_id');
            $table->timestamps();

            $table->foreign('jar_template_jar_id', 'fk_jtjc_tpljar')->references('id')->on('jar_template_jars')->onDelete('cascade');
            $table->foreign('category_template_id', 'fk_jtjc_cattpl')->references('id')->on('category_templates')->onDelete('cascade');
            $table->unique(['jar_template_jar_id', 'category_template_id'], 'uniq_jtjc');
        });

        Schema::create('jar_tpljar_base_cat_tpl', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jar_template_jar_id');
            $table->unsignedBigInteger('category_template_id');
            $table->timestamps();

            $table->foreign('jar_template_jar_id', 'fk_jtjbc_tpljar')->references('id')->on('jar_template_jars')->onDelete('cascade');
            $table->foreign('category_template_id', 'fk_jtjbc_cattpl')->references('id')->on('category_templates')->onDelete('cascade');
            $table->unique(['jar_template_jar_id', 'category_template_id'], 'uniq_jtjbc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_tpljar_base_cat_tpl');
        Schema::dropIfExists('jar_tpljar_cat_tpl');
        Schema::dropIfExists('category_templates');
    }
};
