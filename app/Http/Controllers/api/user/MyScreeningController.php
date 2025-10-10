<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\DiabetesScreening;
use Illuminate\Http\Request;

class MyScreeningController extends Controller
{
    // GET /api/user/diabetes-screenings
    public function index(Request $r)
    {
        $uid = $r->user()->id;
        $rows = DiabetesScreening::with('user')
            ->where('user_id', $uid)
            ->latest('created_at')
            ->get()
            ->map(function ($x) {
                return [
                    'id'         => $x->id,
                    'patient_name' => $x->patient_name ?: optional($x->user)->name,
                    'created_at' => optional($x->created_at)->toAtomString(),
                    'bmi'        => $x->bmi,
                    'blood_glucose_level' => $x->blood_glucose_level,
                    'systolic_bp'   => $x->systolic_bp,
                    'diastolic_bp'  => $x->diastolic_bp,
                    'diabetes_probability' => $x->diabetes_probability,
                ];
            })->values();

        return response()->json($rows);
    }

    // GET /api/user/diabetes-screenings/{id}
    public function show(Request $r, $id)
    {
        $uid = $r->user()->id;
        $x = DiabetesScreening::with('user')
            ->where('user_id', $uid) // hard guard: hanya milik user login
            ->findOrFail($id);

        // (pakai formatter yang sama dengan sebelumnya)
        return response()->json([
            'id'            => $x->id,
            'created_at'    => optional($x->created_at)->toAtomString(),
            'updated_at'    => optional($x->updated_at)->toAtomString(),
            'riskPct'       => $x->diabetes_probability,
            'riskLabel'     => (is_numeric($x->diabetes_probability) && $x->diabetes_probability >= 60) ? 'Risiko Tinggi' : 'Risiko Rendah',
            'nama'          => $x->patient_name ?: optional($x->user)->name,
            'usia'          => $x->age,
            'jenis_kelamin' => $x->gender,
            'bmi'           => $x->bmi,
            'sistolik'      => $x->systolic_bp,
            'diastolik'     => $x->diastolic_bp,
            'klasifikasi_hipertensi' => $x->bp_classification ?? null,
            'riwayat_merokok' => $x->smoking_history,
            'riwayat_jantung' => $x->heart_disease,
            'gula_darah'       => $x->blood_glucose_level ? "{$x->blood_glucose_level} mg/dL" : null,
            'blood_sugar'      => $x->blood_glucose_level,
        ]);
    }
}
