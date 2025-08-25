<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop old unique if exists
            if (Schema::hasColumn('categories', 'user_id')) {
                try { $table->dropUnique('uniq_cat_user_name'); } catch (\Throwable $e) { /* ignore if not exists */ }
            }
            // Add new composite unique for sibling-level uniqueness
            $table->unique(['user_id','parent_id','name'], 'uniq_cat_user_parent_name');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('uniq_cat_user_parent_name');
            // Restore previous unique
            $table->unique(['user_id','name'], 'uniq_cat_user_name');
        });
    }
};
