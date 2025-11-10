<?php

namespace App\Http\Controllers\Api\Manajemen;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /api/manajemen/statistics untuk dashboard
    public function statistics(): JsonResponse
    {
        // statistik pengguna dari tabel users
        $usersBase = DB::table('users')
            ->whereNotNull('kelurahan')
            ->where(function ($q) {
                $q->whereNotNull('tanggal_lahir')
                  ->orWhereNotNull('berat_badan')
                  ->orWhereNotNull('tinggi_badan');
            });

        // Total pengguna yang sudah melengkapi profil
        $totalUsersWithProfile = (int) $usersBase->count();

        // Hitung user per kelurahan 
        $byKel = DB::table('users')
            ->selectRaw("
                SUM(CASE WHEN LOWER(kelurahan) = 'pedalangan' THEN 1 ELSE 0 END) AS total_pedalangan,
                SUM(CASE WHEN LOWER(kelurahan) = 'padangsari' THEN 1 ELSE 0 END) AS total_padangsari
            ")
            ->whereNotNull('kelurahan')
            ->where(function ($q) {
                $q->whereNotNull('tanggal_lahir')
                  ->orWhereNotNull('berat_badan')
                  ->orWhereNotNull('tinggi_badan');
            })
            ->first();

        $totalPedalangan = (int) ($byKel->total_pedalangan ?? 0);
        $totalPadangsari = (int) ($byKel->total_padangsari ?? 0);

        // statistik screening dari tabel diabetes_screenings
        $riskRow = DB::table('diabetes_screenings')
            ->selectRaw("
                SUM(
                    CASE 
                        WHEN LOWER(diabetes_result) REGEXP 'tidak[[:space:]]+berisiko'
                        THEN 1 ELSE 0
                    END
                ) AS normal,

                SUM(
                    CASE
                        WHEN (
                            LOWER(diabetes_result) REGEXP 'perlu[[:space:]]+perhatian|risiko[[:space:]]+sedang'
                        )
                        AND NOT (LOWER(diabetes_result) REGEXP 'tidak[[:space:]]+berisiko')
                        THEN 1 ELSE 0
                    END
                ) AS perhatian,

                SUM(
                    CASE
                        WHEN (
                            LOWER(diabetes_result) REGEXP 'memiliki[[:space:]]+risiko'
                            OR (
                                LOWER(diabetes_result) REGEXP 'berisiko'
                                AND NOT LOWER(diabetes_result) REGEXP 'tidak[[:space:]]+berisiko'
                            )
                        )
                        THEN 1 ELSE 0
                    END
                ) AS risiko
            ")
            ->first();

        $normal    = (int) ($riskRow->normal ?? 0);
        $perhatian = (int) ($riskRow->perhatian ?? 0);
        $risiko    = (int) ($riskRow->risiko ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                // data kartu atas (statistik user))
                'total_keseluruhan' => $totalUsersWithProfile,
                'total_pedalangan'  => $totalPedalangan,
                'total_padangsari'  => $totalPadangsari,

                // data kartu bawah (ringkasan screenings)
                'risk_summary' => [
                    'normal'    => $normal,
                    'perhatian' => $perhatian,
                    'risiko'    => $risiko,
                ],
            ],
        ], 200);
    }
}
