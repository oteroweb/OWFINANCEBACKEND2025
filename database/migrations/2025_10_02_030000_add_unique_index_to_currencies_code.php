<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean duplicates by code, remapping FKs before deleting extras; then add unique index
        if (Schema::hasTable('currencies')) {
            // Find codes with duplicates, keeping the smallest ID per code
            $dupeGroups = DB::table('currencies')
                ->select('code', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
                ->groupBy('code')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($dupeGroups as $group) {
                $keepId = (int)$group->keep_id;
                $code = $group->code;

                // Gather duplicate IDs excluding the keepId
                $dupIds = DB::table('currencies')
                    ->where('code', $code)
                    ->where('id', '!=', $keepId)
                    ->pluck('id')
                    ->all();

                if (!empty($dupIds)) {
                    // Remap FKs in referencing tables to keepId
                    if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'currency_id')) {
                        DB::table('accounts')->whereIn('currency_id', $dupIds)->update(['currency_id' => $keepId]);
                    }
                    if (Schema::hasTable('users') && Schema::hasColumn('users', 'currency_id')) {
                        DB::table('users')->whereIn('currency_id', $dupIds)->update(['currency_id' => $keepId]);
                    }

                    // Now safe to delete duplicate currencies
                    DB::table('currencies')->whereIn('id', $dupIds)->delete();
                }
            }

            Schema::table('currencies', function (Blueprint $table) {
                // Add unique index on code if not already present
                try { $table->unique('code', 'uniq_currencies_code'); } catch (\Throwable $e) { /* ignore if already exists */ }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('currencies')) {
            Schema::table('currencies', function (Blueprint $table) {
                // Drop the unique index if present
                try { $table->dropUnique('uniq_currencies_code'); } catch (\Throwable $e) { /* ignore */ }
            });
        }
    }
};
