<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('item_categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('name');
                $table->foreign('parent_id')->references('id')->on('item_categories')->onDelete('set null');
                $table->index('parent_id');
            }
        });
        // Optional uniqueness to avoid duplicate siblings
        Schema::table('item_categories', function (Blueprint $table) {
            $table->unique(['parent_id','name'], 'uniq_itemcat_parent_name');
        });
    }

    public function down(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            if (Schema::hasColumn('item_categories', 'parent_id')) {
                $table->dropUnique('uniq_itemcat_parent_name');
                $table->dropConstrainedForeignId('parent_id');
                $table->dropColumn('parent_id');
            }
        });
    }
};
