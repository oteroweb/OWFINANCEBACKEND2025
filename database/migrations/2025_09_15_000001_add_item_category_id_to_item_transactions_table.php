<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_transactions', function (Blueprint $table) {
            // Add new nullable FK to item_categories without dropping legacy category_id yet (for backward compatibility)
            if (!Schema::hasColumn('item_transactions', 'item_category_id')) {
                $table->unsignedBigInteger('item_category_id')->nullable()->after('category_id');
                $table->foreign('item_category_id')->references('id')->on('item_categories')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('item_transactions', 'item_category_id')) {
                // drop foreign key first (name is convention-based: item_transactions_item_category_id_foreign)
                try { $table->dropForeign(['item_category_id']); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('item_category_id');
            }
        });
    }
};
