<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('family_group_id');
            $table->unsignedBigInteger('user_id');
            // OWF: un usuario puede pertenecer a varios grupos familiares a la vez —
            // confirmado con el usuario, no se limita a uno solo.
            $table->string('role')->default('member'); // admin | member
            $table->string('status')->default('invited'); // invited | active
            $table->unsignedBigInteger('invited_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('family_group_id')->references('id')->on('family_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['family_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_group_members');
    }
};
