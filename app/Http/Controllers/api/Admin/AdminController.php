<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'totalAdmin'     => User::whereIn('role', ['admin','super_admin'])->count(),
                'totalManajemen' => User::where('role', 'manajemen')->count(),
                'totalNakes'     => User::where('role', 'nakes')->count(),
                'totalUser'      => User::where('role', 'user')->count(),
            ],
        ]);
    }
}
