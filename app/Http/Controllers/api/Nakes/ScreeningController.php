<?php

namespace App\Http\Controllers\Api\Nakes;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScreeningRequest;
use App\Models\DiabetesScreening;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
            'riskText' => is_numeric($pct) ? number_format($pct, 2) . '%' : null,
            'user_id' => $x->user_id,
        ];
    }

    private function toDetail(DiabetesScreening $x): array
    {
        $pct = $this->parsePercent($x->diabetes_probability);
        $label = is_numeric($pct)
            ? ($pct >= 48 ? 'Risiko Tinggi'
                : ($pct <= 40 ? 'Risiko Rendah' : 'Risiko Sedang'))
            : null;

        return [
            'id'            => $x->id,
            'created_at'    => $x->created_at ? $x->created_at->toIso8601String() : null,
            'updated_at'    => $x->updated_at ? $x->updated_at->toIso8601String() : null,
            'riskPct'       => $pct,
            'riskText'      => is_numeric($pct) ? number_format($pct, 2) . '%' : null,
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
    Log::info('=== PAYLOAD MASUK DARI FRONTEND ===', $request->all());

    $payload = $request->validated();

    Log::info('=== PAYLOAD SETELAH VALIDATED ===', $payload);

    // ✅ FIX: Validasi nakesId - pastikan exist di users table
    $nakesId = null;
    if (!empty($payload['nakesId']) && $payload['nakesId'] > 0) {
        // Cek apakah user dengan ID ini ada di database DAN role-nya nakes
        $nakesExists = \App\Models\User::where('id', $payload['nakesId'])
                                       ->whereIn('role', ['nakes', 'admin'])
                                       ->exists();
        if ($nakesExists) {
            $nakesId = $payload['nakesId'];
            Log::info('✅ Nakes ID valid', ['nakesId' => $nakesId]);
        } else {
            Log::warning('⚠️ Nakes ID tidak ditemukan atau bukan role nakes', [
                'nakesId' => $payload['nakesId']
            ]);
        }
    } else {
        Log::warning('⚠️ nakesId kosong atau 0', ['nakesId' => $payload['nakesId'] ?? 'null']);
    }

    // Encode full_result jadi JSON string
    $fullResult = null;
    if (isset($payload['full_result']) && is_array($payload['full_result'])) {
        $fullResult = json_encode($payload['full_result']);
        Log::info('=== FULL_RESULT ENCODED ===', ['full_result' => $fullResult]);
    }

    // Ambil diabetes_probability
    $diabetesProb = $payload['diabetes_probability'] ?? null;
    if (empty($diabetesProb) && isset($payload['full_result']['probabilitas_diabetes'])) {
        $diabetesProb = $payload['full_result']['probabilitas_diabetes'];
        Log::info('=== DIABETES PROBABILITY DIAMBIL DARI FULL_RESULT ===', [
            'probability' => $diabetesProb
        ]);
    }

    try {
        $screening = DiabetesScreening::create([
            'patient_name'          => $payload['patientName'],
            'user_id'               => $payload['userId'] ?? null,
            'nakes_id'              => $nakesId,  // ✅ Sudah divalidasi, bisa null kalau invalid
            'age'                   => $payload['age'] ?? null,
            'gender'                => $payload['gender'] ?? null,
            'systolic_bp'           => $payload['systolic_bp'] ?? null,
            'diastolic_bp'          => $payload['diastolic_bp'] ?? null,
            'heart_disease'         => $payload['heart_disease'] ?? 'Tidak',
            'smoking_history'       => $payload['smoking_history'] ?? null,
            'bmi'                   => $payload['bmi'] ?? null,
            'blood_glucose_level'   => $payload['blood_glucose_level'] ?? null,
            'diabetes_probability'  => $diabetesProb,
            'diabetes_result'       => $payload['diabetes_result'] ?? null,
            'bp_classification'     => $payload['bp_classification'] ?? null,
            'bp_recommendation'     => $payload['bp_recommendation'] ?? null,
            'full_result'           => $fullResult,
        ]);

        Log::info('=== ✅ SCREENING BERHASIL DISIMPAN ===', [
            'id' => $screening->id,
            'patient_name' => $screening->patient_name,
            'nakes_id' => $screening->nakes_id,
        ]);

        return response()->json([
            'success' => true,
            'id'      => $screening->id,
            'data'    => $screening,
        ], 201);
        
    } catch (\Exception $e) {
        Log::error('=== ❌ ERROR SAAT MENYIMPAN SCREENING ===', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'success' => false,
            'error'   => 'Gagal menyimpan data screening',
            'message' => $e->getMessage(),
        ], 500);
    }
}
}