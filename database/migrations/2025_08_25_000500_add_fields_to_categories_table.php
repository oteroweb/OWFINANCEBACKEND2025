<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon', 64)->nullable()->after('name');
            }
            if (!Schema::hasColumn('categories', 'transaction_type_id')) {
                $table->unsignedBigInteger('transaction_type_id')->nullable()->after('parent_id');
                $table->foreign('transaction_type_id', 'fk_categories_transaction_type')
                    ->references('id')->on('transaction_types')
                    ->onDelete('set null');
            }
            if (!Schema::hasColumn('categories', 'include_in_balance')) {
                $table->boolean('include_in_balance')->default(true)->after('transaction_type_id');
            }
            if (!Schema::hasColumn('categories', 'type')) {
                $table->enum('type', ['folder', 'category'])->default('category')->after('include_in_balance');
            }
            if (!Schema::hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'transaction_type_id')) {
                try { $table->dropForeign('fk_categories_transaction_type'); } catch (\Throwable $e) { /* ignore */ }
                $table->dropColumn('transaction_type_id');
            }
            if (Schema::hasColumn('categories', 'icon')) {
                $table->dropColumn('icon');
            }
            if (Schema::hasColumn('categories', 'include_in_balance')) {
                $table->dropColumn('include_in_balance');
            }
            if (Schema::hasColumn('categories', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('categories', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
