<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jars', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('percent', 10, 2)->nullable();
            $table->string('type')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->softDeletes();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar');
    }
};
