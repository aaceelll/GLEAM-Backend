<?php

namespace App\Http\Controllers\Api\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/user/dashboard/summary
     * Ambil glukosa terakhir milik user (tabel diabetes_screenings).
     * Mengembalikan created_at dalam bentuk epoch ms & ISO untuk akurasi zona waktu.
     */
    public function summary(): JsonResponse
    {
        $userId = Auth::id();

        $row = DB::table('diabetes_screenings')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->select('blood_glucose_level', 'diabetes_result', 'created_at')
            ->first();

        if (!$row) {
            return response()->json([
                'success' => true,
                'data' => [
                    'last_glucose'        => null,
                    'last_created_at_iso' => null,
                    'last_created_at_ms'  => null,
                    'label'               => null,
                ],
            ], 200);
        }

        // Normalisasi waktu ke timezone app (config/app.php -> 'timezone' = 'Asia/Jakarta')
        $created = Carbon::parse($row->created_at)->timezone(config('app.timezone'));

        // Label opsional dari diabetes_result
        $res   = strtolower($row->diabetes_result ?? '');
        $label = null;
        if (preg_match('/tidak\s+berisiko/ui', $res)) {
            $label = 'Normal';
        } elseif (preg_match('/perlu\s+perhatian|sedang/ui', $res)) {
            $label = 'Perlu Perhatian';
        } elseif (
            preg_match('/memiliki\s+risiko/ui', $res)
            || (preg_match('/\bberisiko\b/ui', $res) && !preg_match('/tidak\s+berisiko/ui', $res))
        ) {
            $label = 'Risiko';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'last_glucose'        => (float) $row->blood_glucose_level,
                'last_created_at_iso' => $created->toIso8601String(), // untuk display opsional
                'last_created_at_ms'  => (int) $created->valueOf(),    // epoch milliseconds (AKURAT)
                'label'               => $label,
            ],
        ], 200);
    }
}
