<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DiabetesScreening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * GET /api/locations/users
     * Ambil semua user dengan lokasi lengkap (role=user)
     */
    public function getUsersWithLocations()
    {
        try {
            $users = User::where('role', 'user')
                ->whereNotNull('kelurahan')
                ->whereNotNull('rw')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select([
                    'id', 'nama', 'email', 'nomor_telepon',
                    'kelurahan', 'rw', 'alamat', 'address',
                    'latitude', 'longitude'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $users,
                'total'   => $users->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data lokasi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/locations/statistics
     * Tambahan:
     * - risk_summary { normal, perhatian, risiko }
     * - affected_percentage (distinct user yang pernah screening / total user)
     */
    public function getStatistics()
    {
        try {
            // ---------- Perhitungan existing (tetap dipertahankan) ----------
            $pedalangan = User::where('role', 'user')
                ->where('kelurahan', 'Pedalangan')
                ->whereNotNull('latitude')
                ->count();

            $padangsari = User::where('role', 'user')
                ->where('kelurahan', 'Padangsari')
                ->whereNotNull('latitude')
                ->count();

            $total = $pedalangan + $padangsari;

            // Distribusi per-RW Pedalangan
            $pedalanganRW = User::where('role', 'user')
                ->where('kelurahan', 'Pedalangan')
                ->whereNotNull('rw')
                ->whereNotNull('latitude')
                ->select('rw', DB::raw('count(*) as total'))
                ->groupBy('rw')
                ->get()
                ->keyBy('rw');

            // Distribusi per-RW Padangsari
            $padangsariRW = User::where('role', 'user')
                ->where('kelurahan', 'Padangsari')
                ->whereNotNull('rw')
                ->whereNotNull('latitude')
                ->select('rw', DB::raw('count(*) as total'))
                ->groupBy('rw')
                ->get()
                ->keyBy('rw');

            // Normalize RW Pedalangan (1..11)
            $pedalanganData = [];
            for ($i = 1; $i <= 11; $i++) {
                $rwKey = "RW $i";
                $pedalanganData[] = [
                    'rw'    => $rwKey,
                    'count' => $pedalanganRW->get($rwKey)?->total ?? 0,
                ];
            }

            // Normalize RW Padangsari (1..17)
            $padangsariData = [];
            for ($i = 1; $i <= 17; $i++) {
                $rwKey = "RW $i";
                $padangsariData[] = [
                    'rw'    => $rwKey,
                    'count' => $padangsariRW->get($rwKey)?->total ?? 0,
                ];
            }

            // ---------- Tambahan: data screening real dari DB ----------
            // Distinct user yang pernah screening (untuk affected % dan total kasus unik)
            $screenedUserIds = DiabetesScreening::query()
                ->whereNotNull('user_id')
                ->where('user_id', '>', 0)
                ->distinct()
                ->pluck('user_id');

            $totalDistinctScreened = $screenedUserIds->count();
            $totalUsers            = User::query()->count();
            $affectedPercentage    = $totalUsers > 0
                ? (int) round(($totalDistinctScreened / $totalUsers) * 100)
                : 0;

            // Ringkasan risiko:
            //  - jika diabetes_probability numerik → gunakan threshold 0.33 / 0.66
            //  - fallback ke keyword di diabetes_result
            $risk = DiabetesScreening::query()
                ->selectRaw("
                    SUM(
                        CASE
                          WHEN (diabetes_probability REGEXP '^[0-9]+(\\.[0-9]+)?$'
                                AND CAST(diabetes_probability AS DECIMAL(10,4)) < 0.33)
                               OR (LOWER(diabetes_result) LIKE '%normal%')
                          THEN 1 ELSE 0 END
                    ) as normal,
                    SUM(
                        CASE
                          WHEN (diabetes_probability REGEXP '^[0-9]+(\\.[0-9]+)?$'
                                AND CAST(diabetes_probability AS DECIMAL(10,4)) >= 0.33
                                AND CAST(diabetes_probability AS DECIMAL(10,4)) <= 0.66)
                               OR (LOWER(diabetes_result) LIKE '%perhatian%' OR LOWER(diabetes_result) LIKE '%sedang%')
                          THEN 1 ELSE 0 END
                    ) as perhatian,
                    SUM(
                        CASE
                          WHEN (diabetes_probability REGEXP '^[0-9]+(\\.[0-9]+)?$'
                                AND CAST(diabetes_probability AS DECIMAL(10,4)) > 0.66)
                               OR (LOWER(diabetes_result) LIKE '%tinggi%' OR LOWER(diabetes_result) LIKE '%high%')
                          THEN 1 ELSE 0 END
                    ) as risiko
                ")
                ->first();

            return response()->json([
                'success' => true,
                'data'    => [
                    // existing fields (dipakai halaman peta)
                    'total_keseluruhan' => $total,
                    'total_pedalangan'  => $pedalangan,
                    'total_padangsari'  => $padangsari,
                    'pedalangan_rw'     => $pedalanganData,
                    'padangsari_rw'     => $padangsariData,

                    // new for dashboard health section
                    'risk_summary' => [
                        'normal'    => (int) ($risk->normal ?? 0),
                        'perhatian' => (int) ($risk->perhatian ?? 0),
                        'risiko'    => (int) ($risk->risiko ?? 0),
                    ],
                    'affected_percentage' => $affectedPercentage,

                    // jika kamu ingin pakai total kasus unik:
                    'distinct_screened_users' => $totalDistinctScreened,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/locations/users-by-rw?kelurahan=Pedalangan&rw=RW 1
     */
    public function getUsersByRW(Request $request)
    {
        $request->validate([
            'kelurahan' => 'required|in:Pedalangan,Padangsari',
            'rw'        => 'required|string',
        ]);

        try {
            $users = User::where('role', 'user')
                ->where('kelurahan', $request->kelurahan)
                ->where('rw', $request->rw)
                ->whereNotNull('latitude')
                ->select([
                    'id', 'nama', 'email', 'nomor_telepon',
                    'alamat', 'address', 'kelurahan', 'rw',
                    'latitude', 'longitude'
                ])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $users,
                'total'   => $users->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/locations/user/{id}
     */
    public function getUserDetail($id)
    {
        try {
            $user = User::where('role', 'user')
                ->where('id', $id)
                ->select([
                    'id','nama','email','nomor_telepon','alamat','address',
                    'kelurahan','rw','latitude','longitude',
                    'tanggal_lahir','jenis_kelamin','pekerjaan',
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
                'data'    => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail user: '.$e->getMessage(),
            ], 500);
        }
    }
}
