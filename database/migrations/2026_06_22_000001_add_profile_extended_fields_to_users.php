<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('occupation', 100)->nullable()->after('phone');
            $table->string('city', 100)->nullable()->after('occupation');
            $table->string('country', 10)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['occupation', 'city', 'country']);
        });
    }
};
