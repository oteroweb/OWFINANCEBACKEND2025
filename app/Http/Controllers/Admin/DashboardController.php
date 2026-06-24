<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'Admin dashboard',
            'data'   => [
                'total_users'              => User::count(),
                'active_users'             => User::where('active', true)->count(),
                'total_transactions'       => DB::table('transactions')->count(),
                'transactions_this_month'  => DB::table('transactions')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'total_accounts'           => DB::table('accounts')->whereNull('deleted_at')->count(),
                'total_jars'               => DB::table('jars')->count(),
            ],
        ]);
    }
}
