<?php

namespace App\Http\Controllers\Api\Manajemen;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/manajemen/statistics
     */
    public function statistics(): JsonResponse
    {
        // =========================
        //  A. KARTU ATAS (users)
        // =========================
        $usersBase = DB::table('users')
            ->whereNotNull('kelurahan')
            ->where(function ($q) {
                $q->whereNotNull('tanggal_lahir')
                  ->orWhereNotNull('berat_badan')
                  ->orWhereNotNull('tinggi_badan');
            });

        $totalUsersWithProfile = (int) $usersBase->count();

        // Hitung per kelurahan (case-insensitive)
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

        // =========================
        //  B. KARTU BAWAH (screenings)
        // =========================
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

        // Total baris screening (kalau mau unique user → pakai DISTINCT user_id)
        $totalScreenings = (int) DB::table('diabetes_screenings')->count();

        $affectedPct = $totalScreenings > 0
            ? round((($perhatian + $risiko) / $totalScreenings) * 100, 2)
            : 0.0;

        return response()->json([
            'success' => true,
            'data' => [
                // 3 kartu atas (users)
                'total_keseluruhan' => $totalUsersWithProfile,
                'total_pedalangan'  => $totalPedalangan,
                'total_padangsari'  => $totalPadangsari,

                // 3 kartu bawah (screenings)
                'risk_summary' => [
                    'normal'    => $normal,
                    'perhatian' => $perhatian,
                    'risiko'    => $risiko,
                ],
                'affected_percentage' => $affectedPct,
            ],
        ], 200);
    }
}
