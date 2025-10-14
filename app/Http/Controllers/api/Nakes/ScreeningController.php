<?php

namespace App\Http\Controllers\Api\Nakes;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScreeningRequest;
use App\Models\DiabetesScreening;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScreeningController extends Controller
{
    // GET /api/nakes/diabetes-screenings
    public function index(Request $r)
    {
        $q = DiabetesScreening::query()->with('user')->latest('created_at');

        if ($search = $r->query('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('patient_name', 'like', "%{$search}%");
            });
        }

        $rows = $q->get()->map(fn($x) => $this->toListRow($x))->values();
        return response()->json($rows);
    }

    // GET /api/nakes/diabetes-screenings/{id}
    public function show($id)
    {
        $x = DiabetesScreening::with('user')->findOrFail($id);
        return response()->json($this->toDetail($x));
    }

    // GET /api/nakes/users/{userId}/diabetes-screenings
    public function byUser($userId)
    {
        $rows = DiabetesScreening::where('user_id', $userId)
            ->latest('created_at')
            ->get()
            ->map(fn($x) => $this->toListRow($x))
            ->values();

        return response()->json($rows);
    }

    private function toListRow(DiabetesScreening $x): array
    {
        $pct = $this->parsePercent($x->diabetes_probability);

        return [
            'id'      => $x->id,
            'name'    => $x->patient_name ?: optional($x->user)->name,
            'date'    => $x->created_at ? $x->created_at->toIso8601String() : null,
            'riskPct' => $pct,
            'user_id' => $x->user_id,
        ];
    }

    private function toDetail(DiabetesScreening $x): array
    {
        $pct = $this->parsePercent($x->diabetes_probability);
        $label = is_numeric($pct) && $pct >= 60 ? 'Risiko Tinggi' : 'Risiko Rendah';

        return [
            'id'            => $x->id,
            'created_at'    => $x->created_at ? $x->created_at->toIso8601String() : null,
            'updated_at'    => $x->updated_at ? $x->updated_at->toIso8601String() : null,
            'riskPct'       => $pct,
            'riskLabel'     => $label,
            'nama'          => $x->patient_name ?: optional($x->user)->name,
            'usia'          => $x->age,
            'jenis_kelamin' => $x->gender,
            'bmi'           => $x->bmi,
            'sistolik'      => $x->systolic_bp,
            'diastolik'     => $x->diastolic_bp,
            'tekanan_darah' => ($x->systolic_bp && $x->diastolic_bp) ? "{$x->systolic_bp} / {$x->diastolic_bp}" : null,
            'riwayat_merokok' => $x->smoking_history,
            'riwayat_jantung' => $x->heart_disease,
            'gula_darah'       => $x->blood_glucose_level ? "{$x->blood_glucose_level} mg/dL" : null,
            'blood_sugar'      => $x->blood_glucose_level,
        ];
    }

    private function parsePercent($val): ?float
    {
        if ($val === null) return null;
        $num = is_string($val) ? str_replace('%', '', $val) : $val;
        $f = floatval($num);
        return is_finite($f) ? $f : null;
    }

    public function store(StoreScreeningRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $screening = DiabetesScreening::create([
            'patient_name'          => $payload['patientName'],
            'user_id'               => $payload['userId'] ?? null,
            'nakes_id'              => $payload['nakesId'] ?? null,

            'age'                   => $payload['age'] ?? null,
            'gender'                => $payload['gender'] ?? null,

            'systolic_bp'           => $payload['systolic_bp'] ?? null,
            'diastolic_bp'          => $payload['diastolic_bp'] ?? null,
            'heart_disease'         => $payload['heart_disease'] ?? false,
            'smoking_history'       => $payload['smoking_history'] ?? null,

            'bmi'                   => $payload['bmi'] ?? null,
            'blood_glucose_level'   => $payload['blood_glucose_level'] ?? null,
            'diabetes_probability'  => $payload['diabetes_probability'] ?? null,
            'diabetes_result'       => $payload['diabetes_result'] ?? null,

            'bp_classification'     => $payload['bp_classification'] ?? null,
            'bp_recommendation'     => $payload['bp_recommendation'] ?? null,

            'full_result'           => $payload['full_result'] ?? null, // otomatis cast ke JSON
        ]);

        return response()->json([
            'success' => true,
            'id'      => $screening->id,
            'data'    => $screening,
        ], 201);
    }

        public function latest(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        if ($limit < 1)   $limit = 1;
        if ($limit > 50)  $limit = 50;

        // Pilih kolom yang dibutuhkan saja (opsional)
        $columns = [
            'id',
            'patient_name',
            'user_id',
            'nakes_id',
            'age',
            'gender',
            'systolic_bp',
            'diastolic_bp',
            'heart_disease',
            'smoking_history',
            'bmi',
            'blood_glucose_level',
            'diabetes_probability',
            'diabetes_result',
            'bp_classification',
            'bp_recommendation',
            'full_result',
            'created_at',
            'updated_at',
        ];

        $latest = DiabetesScreening::select($columns)
            ->orderByDesc('created_at')
            ->first();

        $history = DiabetesScreening::select($columns)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'latest'  => $latest,            // null jika belum ada data
            'history' => $history,           // array (0..limit)
        ], 200);
    }
}
