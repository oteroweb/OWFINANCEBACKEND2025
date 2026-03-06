<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_folders', function (Blueprint $table) {
            if (!Schema::hasColumn('account_folders', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('parent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_folders', function (Blueprint $table) {
            if (Schema::hasColumn('account_folders', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
