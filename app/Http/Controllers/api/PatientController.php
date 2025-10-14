<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['patients' => []], 200);
        }

        $rows = User::patients()
            ->where('nama', 'like', "%{$q}%")
            ->select([
                'id',
                'nama',
                'umur',
                'jenis_kelamin',
                'riwayat_pelayanan_kesehatan',
                'riwayat_penyakit_jantung',
                'riwayat_merokok',
                'indeks_bmi',
            ])
            ->limit(10)
            ->get();

        // Kembalikan dalam bentuk { patients: [...] }
        return response()->json([
            'patients' => PatientResource::collection($rows),
        ], 200);
    }
}
