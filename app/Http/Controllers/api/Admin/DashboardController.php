<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // ✅ tambahin ini

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $data = [
                'totalAdmin'     => User::where('role', 'admin')->count(),
                'totalManajemen' => User::where('role', 'manajemen')->count(),
                'totalNakes'     => User::where('role', 'nakes')->count(),
                'totalUser'      => User::where('role', 'user')->count(),
            ];

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            // Logging error biar gampang trace
            Log::error('Admin Dashboard error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dashboard',
            ], 500);
        }
    }
}
