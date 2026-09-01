<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            if (!Schema::hasColumn('account_types', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id', 'idx_account_types_user');
                $table->foreign('user_id', 'fk_account_types_user')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            if (Schema::hasColumn('account_types', 'user_id')) {
                $table->dropForeign('fk_account_types_user');
                $table->dropIndex('idx_account_types_user');
                $table->dropColumn('user_id');
            }
        });
    }
};
