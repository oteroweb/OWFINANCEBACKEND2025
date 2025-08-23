<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_templates');
    }
};
