<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!config('features.drop_legacy_item_tx_category')) {
            // Feature flag disabled: skip dropping the column
            return;
        }

        Schema::table('item_transactions', function (Blueprint $table) {
            // Drop FK and column if they exist
            try {
                $table->dropForeign(['category_id']);
            } catch (Throwable $e) {
                // ignore if FK doesn't exist
            }
            if (Schema::hasColumn('item_transactions', 'category_id')) {
                $table->dropColumn('category_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('item_transactions', 'category_id')) {
            Schema::table('item_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('item_id');
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });
        }
    }
};
