<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            if (!Schema::hasColumn('jars', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('jars', 'fixed_amount')) {
                $table->decimal('fixed_amount', 12, 2)->nullable()->after('percent');
            }
            if (!Schema::hasColumn('jars', 'base_scope')) {
                $table->enum('base_scope', ['all_income', 'categories'])->default('all_income')->after('type');
            }
            if (!Schema::hasColumn('jars', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('active');
            }
            if (!Schema::hasColumn('jars', 'color')) {
                $table->string('color', 16)->nullable()->after('sort_order');
            }
        });

        Schema::table('jars', function (Blueprint $table) {
            if (Schema::hasColumn('jars', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            if (Schema::hasColumn('jars', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('jars', 'fixed_amount')) {
                $table->dropColumn('fixed_amount');
            }
            if (Schema::hasColumn('jars', 'base_scope')) {
                $table->dropColumn('base_scope');
            }
            if (Schema::hasColumn('jars', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('jars', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
