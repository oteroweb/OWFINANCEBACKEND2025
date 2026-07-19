<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_extractions', function (Blueprint $table) {
            $table->json('missing_fields')->nullable()->after('extracted_data');
            $table->boolean('direct_create')->default(false)->after('missing_fields');
            $table->boolean('resolved')->default(true)->after('direct_create');
        });
    }

    public function down(): void
    {
        Schema::table('ai_extractions', function (Blueprint $table) {
            $table->dropColumn(['missing_fields', 'direct_create', 'resolved']);
        });
    }
};
