<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    public function index()
    {
        $tables = [
            'users', 'accounts', 'jars', 'transactions', 'categories',
            'roles', 'currencies', 'debts', 'dreams', 'jar_templates',
        ];

        $counts = [];
        foreach ($tables as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (\Exception $e) {
                $counts[$table] = null;
            }
        }

        try {
            $lastLogins = DB::table('users')
                ->whereNotNull('last_login_at')
                ->orderByDesc('last_login_at')
                ->limit(5)
                ->get(['id', 'name', 'email', 'last_login_at'])
                ->toArray();
        } catch (\Exception $e) {
            $lastLogins = DB::table('users')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'name', 'email', 'updated_at as last_login_at'])
                ->toArray();
        }

        $deployInfo = [
            'php_version'  => PHP_VERSION,
            'laravel'      => app()->version(),
            'env'          => config('app.env'),
            'cache_driver' => config('cache.default'),
            'db_driver'    => config('database.default'),
            'server_time'  => now()->toIso8601String(),
        ];

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'data'   => [
                'table_counts' => $counts,
                'last_logins'  => $lastLogins,
                'deploy'       => $deployInfo,
            ],
        ]);
    }
}
