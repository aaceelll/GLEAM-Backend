<?php

namespace App\Http\Controllers\Api\Nakes;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScreeningRequest;
use App\Models\DiabetesScreening;
use App\Models\User;
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

        $rows = $q->get()->map(fn ($x) => $this->toListRow($x))->values();
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
            ->map(fn ($x) => $this->toListRow($x))
            ->values();

        return response()->json($rows);
    }

    // GET /api/screenings/latest
    // (sudah ada rute-nya di routes/api.php)
    public function latest(Request $r): JsonResponse
    {
        $x = DiabetesScreening::latest('created_at')->first();

        if (!$x) {
            return response()->json(['message' => 'Belum ada data screening.'], 404);
        }

        return response()->json([
            'id'                   => $x->id,
            'created_at'           => $x->created_at?->toIso8601String(),
            'patient_name'         => $x->patient_name ?: optional($x->user)->name,
            'age'                  => (int) ($x->age ?? 0),
            'bmi'                  => (float) ($x->bmi ?? 0),
            'systolic_bp'          => (float) ($x->systolic_bp ?? 0),
            'diastolic_bp'         => (float) ($x->diastolic_bp ?? 0),
            'diabetes_probability' => (string) ($x->diabetes_probability ?? ''),
            'diabetes_result'      => (string) ($x->diabetes_result ?? ''),
            'bp_classification'    => (string) ($x->bp_classification ?? ''),
            'bp_recommendation'    => (string) ($x->bp_recommendation ?? ''),
        ]);
    }

    private function toListRow(DiabetesScreening $x): array
    {
        $pct = $this->parsePercent($x->diabetes_probability);

        return [
            'id'       => $x->id,
            'name'     => $x->patient_name ?: optional($x->user)->name,
            'date'     => $x->created_at ? $x->created_at->toIso8601String() : null,
            'riskPct'  => $pct,
            'riskText' => is_numeric($pct) ? number_format($pct, 2) . '%' : null,
            'user_id'  => $x->user_id,
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
            'id'               => $x->id,
            'created_at'       => $x->created_at ? $x->created_at->toIso8601String() : null,
            'updated_at'       => $x->updated_at ? $x->updated_at->toIso8601String() : null,
            'riskPct'          => $pct,
            'riskText'         => is_numeric($pct) ? number_format($pct, 2) . '%' : null,
            'riskLabel'        => $label,
            'nama'             => $x->patient_name ?: optional($x->user)->name,
            'usia'             => $x->age,
            'jenis_kelamin'    => $x->gender,
            'bmi'              => $x->bmi,
            'sistolik'         => $x->systolic_bp,
            'diastolik'        => $x->diastolic_bp,
            'tekanan_darah'    => ($x->systolic_bp && $x->diastolic_bp) ? "{$x->systolic_bp} / {$x->diastolic_bp}" : null,
            'riwayat_merokok'  => $x->smoking_history,
            'riwayat_jantung'  => $x->heart_disease,
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

        // Ambil/siapkan diabetes_probability (boleh string "48.08%")
        $diabetesProb = $payload['diabetes_probability'] ?? null;
        if (empty($diabetesProb) && isset($payload['full_result']['probabilitas_diabetes'])) {
            $diabetesProb = $payload['full_result']['probabilitas_diabetes'];
            Log::info('=== DIABETES PROBABILITY DIAMBIL DARI FULL_RESULT ===', [
                'probability' => $diabetesProb
            ]);
        }

        // Validasi nakesId (opsional)
        $nakesId = null;
        if (!empty($payload['nakesId']) && $payload['nakesId'] > 0) {
            $nakesExists = User::where('id', $payload['nakesId'])
                ->whereIn('role', ['nakes', 'admin'])
                ->exists();

            if ($nakesExists) {
                $nakesId = $payload['nakesId'];
                Log::info('Nakes ID valid', ['nakesId' => $nakesId]);
            } else {
                Log::warning('⚠️ Nakes ID tidak ditemukan / bukan role nakes', [
                    'nakesId' => $payload['nakesId']
                ]);
            }
        } else {
            Log::warning('⚠️ nakesId kosong atau 0', ['nakesId' => $payload['nakesId'] ?? 'null']);
        }

        // Encode full_result (jika array) agar aman ke kolom TEXT
        $fullResult = $payload['full_result'] ?? null;
        if (is_array($fullResult)) {
            $fullResult = json_encode($fullResult);
        }

        try {
            $screening = DiabetesScreening::create([
                // terima camelCase maupun snake_case
                'patient_name'          => $payload['patient_name'] ?? $payload['patientName'] ?? null,
                'user_id'               => $payload['user_id'] ?? $payload['userId'] ?? null,
                'nakes_id'              => $nakesId,

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

                // pakai hasil form jika ada; jika tidak, fallback dari full_result
                'bp_classification'     => $payload['bp_classification']
                                            ?? ($payload['full_result']['tekanan_darah']['klasifikasi'] ?? null),
                'bp_recommendation'     => $payload['bp_recommendation']
                                            ?? ($payload['full_result']['tekanan_darah']['rekomendasi'] ?? null),

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
        } catch (\Throwable $e) {
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
