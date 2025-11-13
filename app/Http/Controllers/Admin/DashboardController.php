<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Placeholder dashboard endpoint; replace with view or metrics as needed
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Admin dashboard',
        ]);
    }
}
