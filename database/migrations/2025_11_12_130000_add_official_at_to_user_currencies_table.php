<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_currencies', function (Blueprint $table) {
            if (!Schema::hasColumn('user_currencies', 'official_at')) {
                $table->timestamp('official_at')->nullable()->after('is_official');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_currencies', function (Blueprint $table) {
            if (Schema::hasColumn('user_currencies', 'official_at')) {
                $table->dropColumn('official_at');
            }
        });
    }
};
