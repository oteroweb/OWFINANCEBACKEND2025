<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jar_category', function (Blueprint $table) {
            if (!Schema::hasColumn('jar_category', 'active')) {
                $table->tinyInteger('active')->default(1)->after('category_id');
            }
            if (!Schema::hasColumn('jar_category', 'deleted_at')) {
                $table->softDeletes();
            }
            // Drop old unique and add a new composite unique including deleted_at so soft-deleted rows don't block reattach
            try {
                $table->dropUnique('jar_category_jar_id_category_id_unique');
            } catch (\Throwable $e) {
                // ignore if index doesn't exist or driver doesn't support it directly
            }
            // Laravel will name it automatically; we set explicit name for clarity
            $table->unique(['jar_id', 'category_id', 'deleted_at'], 'uniq_jar_cat_del');
        });

        Schema::table('jar_base_category', function (Blueprint $table) {
            if (!Schema::hasColumn('jar_base_category', 'active')) {
                $table->tinyInteger('active')->default(1)->after('category_id');
            }
            if (!Schema::hasColumn('jar_base_category', 'deleted_at')) {
                $table->softDeletes();
            }
            try {
                $table->dropUnique('jar_base_category_jar_id_category_id_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            $table->unique(['jar_id', 'category_id', 'deleted_at'], 'uniq_jar_base_cat_del');
        });
    }

    public function down(): void
    {
        Schema::table('jar_category', function (Blueprint $table) {
            if (Schema::hasColumn('jar_category', 'active')) {
                $table->dropColumn('active');
            }
            if (Schema::hasColumn('jar_category', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            // restore original unique if needed
            $table->unique(['jar_id', 'category_id']);
        });

        Schema::table('jar_base_category', function (Blueprint $table) {
            if (Schema::hasColumn('jar_base_category', 'active')) {
                $table->dropColumn('active');
            }
            if (Schema::hasColumn('jar_base_category', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            $table->unique(['jar_id', 'category_id']);
        });
    }
};
