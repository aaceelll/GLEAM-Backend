<?php

namespace App\Http\Controllers\Api\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DiabetesScreening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    // GET /api/locations/users
    // peta manajemen
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
                    'kelurahan', 'rw', 'address',
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

    // GET /api/locations/statistics
    // Menampilkan ringkasan statistik lokasi dan kesehatan pengguna role manajemen
    public function getStatistics()
    {
        try {
            // Statistik lokasi pengguna per kelurahan
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

            // Normalisasi RW Pedalangan (1-11)
            $pedalanganData = [];
            for ($i = 1; $i <= 11; $i++) {
                $rwKey = "RW $i";
                $pedalanganData[] = [
                    'rw'    => $rwKey,
                    'count' => $pedalanganRW->get($rwKey)?->total ?? 0,
                ];
            }

            // Normalisasi RW Padangsari (1-17)
            $padangsariData = [];
            for ($i = 1; $i <= 17; $i++) {
                $rwKey = "RW $i";
                $padangsariData[] = [
                    'rw'    => $rwKey,
                    'count' => $padangsariRW->get($rwKey)?->total ?? 0,
                ];
            }

            // Ringkasan tingkat risiko
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
                    // data untuk halaman peta
                    'total_keseluruhan' => $total,
                    'total_pedalangan'  => $pedalangan,
                    'total_padangsari'  => $padangsari,
                    'pedalangan_rw'     => $pedalanganData,
                    'padangsari_rw'     => $padangsariData,

                    // data untuk dashboard kesehatan
                    'risk_summary' => [
                        'normal'    => (int) ($risk->normal ?? 0),
                        'perhatian' => (int) ($risk->perhatian ?? 0),
                        'risiko'    => (int) ($risk->risiko ?? 0),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik: '.$e->getMessage(),
            ], 500);
        }
    }

    // GET /api/locations/users-by-rw?kelurahan=Pedalangan&rw=RW 1
    // Menampilkan daftar user di RW tertentu
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
                    'address', 'kelurahan', 'rw',
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

    // GET /api/locations/user/{id}
    public function getUserDetail($id)
    {
        try {
            $user = User::where('role', 'user')
                ->where('id', $id)
                ->select([
                    'id','nama','email','nomor_telepon', 'address',
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
