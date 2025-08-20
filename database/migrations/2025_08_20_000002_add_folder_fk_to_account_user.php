<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_user', function (Blueprint $table) {
            // Ensure columns exist
            $table->unsignedBigInteger('folder_id')->nullable()->change();
            $table->integer('sort_order')->default(0)->change();
            // Add index and foreign key
            $table->index('folder_id');
            $table->foreign('folder_id')
                  ->references('id')
                  ->on('account_folders')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_user', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropIndex(['folder_id']);
        });
    }
};
