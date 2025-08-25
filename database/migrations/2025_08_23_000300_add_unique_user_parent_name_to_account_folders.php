<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_folders', function (Blueprint $table) {
            // Unique name per user and parent (sibling uniqueness)
            $table->unique(['user_id','parent_id','name'], 'uniq_accfolders_user_parent_name');
        });
    }

    public function down(): void
    {
        Schema::table('account_folders', function (Blueprint $table) {
            $table->dropUnique('uniq_accfolders_user_parent_name');
        });
    }
};
