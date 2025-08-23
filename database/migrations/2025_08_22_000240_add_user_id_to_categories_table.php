<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('parent_id');
                $table->index('user_id', 'idx_cat_user');
                $table->foreign('user_id', 'fk_cat_user')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'user_id')) {
                $table->dropForeign('fk_cat_user');
                $table->dropIndex('idx_cat_user');
                $table->dropColumn('user_id');
            }
        });
    }
};
