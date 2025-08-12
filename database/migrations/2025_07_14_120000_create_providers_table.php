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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 35);
            $table->string('description', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 50)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('logo', 100)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('state', 50)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
