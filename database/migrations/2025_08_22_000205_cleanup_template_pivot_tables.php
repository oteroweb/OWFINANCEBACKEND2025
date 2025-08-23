<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safely drop leftover pivot tables from previous failed attempts
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('jar_template_jar_categories');
        Schema::dropIfExists('jar_template_jar_base_categories');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No-op: cleanup migration
    }
};
