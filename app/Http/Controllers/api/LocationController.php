<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Mendapatkan semua user dengan lokasi lengkap
     * GET /api/locations/users
     */
    public function getUsersWithLocations()
    {
        try {
            // Ambil semua user yang punya data lokasi lengkap (role = user)
            $users = User::where('role', 'user')
                ->whereNotNull('kelurahan')
                ->whereNotNull('rw')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select([
                    'id',
                    'nama',
                    'email',
                    'nomor_telepon',
                    'kelurahan',
                    'rw',
                    'alamat',
                    'address',
                    'latitude',
                    'longitude'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'total' => $users->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mendapatkan statistik per kelurahan
     * GET /api/locations/statistics
     */
    public function getStatistics()
    {
        try {
            // Hitung total user per kelurahan
            $pedalangan = User::where('role', 'user')
                ->where('kelurahan', 'Pedalangan')
                ->whereNotNull('latitude')
                ->count();

            $padangsari = User::where('role', 'user')
                ->where('kelurahan', 'Padangsari')
                ->whereNotNull('latitude')
                ->count();

            $total = $pedalangan + $padangsari;

            // Hitung per RW untuk Pedalangan
            $pedalanganRW = User::where('role', 'user')
                ->where('kelurahan', 'Pedalangan')
                ->whereNotNull('rw')
                ->whereNotNull('latitude')
                ->select('rw', DB::raw('count(*) as total'))
                ->groupBy('rw')
                ->get()
                ->keyBy('rw');

            // Hitung per RW untuk Padangsari
            $padangsariRW = User::where('role', 'user')
                ->where('kelurahan', 'Padangsari')
                ->whereNotNull('rw')
                ->whereNotNull('latitude')
                ->select('rw', DB::raw('count(*) as total'))
                ->groupBy('rw')
                ->get()
                ->keyBy('rw');

            // Format data RW untuk Pedalangan (11 RW)
            $pedalanganData = [];
            for ($i = 1; $i <= 11; $i++) {
                $rwKey = "RW $i";
                $pedalanganData[] = [
                    'rw' => $rwKey,
                    'count' => $pedalanganRW->get($rwKey)?->total ?? 0
                ];
            }

            // Format data RW untuk Padangsari (17 RW)
            $padangsariData = [];
            for ($i = 1; $i <= 17; $i++) {
                $rwKey = "RW $i";
                $padangsariData[] = [
                    'rw' => $rwKey,
                    'count' => $padangsariRW->get($rwKey)?->total ?? 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_keseluruhan' => $total,
                    'total_pedalangan' => $pedalangan,
                    'total_padangsari' => $padangsari,
                    'pedalangan_rw' => $pedalanganData,
                    'padangsari_rw' => $padangsariData,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mendapatkan daftar user di RW tertentu
     * GET /api/locations/users-by-rw?kelurahan=Pedalangan&rw=RW 1
     */
    public function getUsersByRW(Request $request)
    {
        $request->validate([
            'kelurahan' => 'required|in:Pedalangan,Padangsari',
            'rw' => 'required|string',
        ]);

        try {
            $users = User::where('role', 'user')
                ->where('kelurahan', $request->kelurahan)
                ->where('rw', $request->rw)
                ->whereNotNull('latitude')
                ->select([
                    'id',
                    'nama',
                    'email',
                    'nomor_telepon',
                    'alamat',
                    'address',
                    'kelurahan',
                    'rw',
                    'latitude',
                    'longitude'
                ])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'total' => $users->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mendapatkan detail user
     * GET /api/locations/user/{id}
     */
    public function getUserDetail($id)
    {
        try {
            $user = User::where('role', 'user')
                ->where('id', $id)
                ->select([
                    'id',
                    'nama',
                    'email',
                    'nomor_telepon',
                    'alamat',
                    'address',
                    'kelurahan',
                    'rw',
                    'latitude',
                    'longitude',
                    'tanggal_lahir',
                    'jenis_kelamin',
                    'pekerjaan',
                ])
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail user: ' . $e->getMessage(),
            ], 500);
        }
    }
}