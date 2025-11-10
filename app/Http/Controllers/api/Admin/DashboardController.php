<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Log; 

class DashboardController extends Controller
{
    // GET /api/admin/dashboard (mengambil ringkasan jumlah akun berdasarkan role)
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
            // Logging error agar mudah ditelusuri
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
