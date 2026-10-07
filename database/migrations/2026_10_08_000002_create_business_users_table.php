<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OWF-370: acceso a una empresa por rol (owner|accountant|viewer), independiente del
// grupo familiar — un contador no es familia. Un acceso empieza 'invited' y pasa a
// 'active' cuando el invitado acepta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 12);
            $table->string('status', 10)->default('active');
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['business_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_users');
    }
};
